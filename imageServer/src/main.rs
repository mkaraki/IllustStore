use std::{env, fs, io, path};
use actix_web::{get, App, HttpServer, HttpRequest, HttpResponse, web, middleware::Logger};
use sqlx::mysql::{MySqlPoolOptions, MySqlPool};
use image::{ImageReader, ImageEncoder, codecs::*};
use fast_image_resize::IntoImageView;
use fast_image_resize::images::Image;
use tracing_subscriber::prelude::*;

mod image_lepton;

#[get("/image/{image_id}/{variant}")]
async fn get_image(
    request: HttpRequest,
    pool: web::Data<MySqlPool>,
    request_info: web::Path<(u32, String)>,
) -> actix_web::HttpResponse {
    let image_id = request_info.0;
    let variant = &request_info.1;

    let resize_size: Option<f32>;
    let encode_quality;

    if variant == "thumb" {
        resize_size = Some(250.0);
        encode_quality = 40;
    } else if variant == "large" {
        resize_size = Some(1920.0);
        encode_quality = 80;
    } else if variant == "raw" {
        resize_size = None;
        encode_quality = 100;
    } else {
        return HttpResponse::BadRequest().into();
    }

    let parent_span = sentry::configure_scope(|scope| scope.get_span());
    let mut db_span: Option<sentry::Span> = None;
    if parent_span.is_some() {
        let parent_span = parent_span.unwrap();
        let span = parent_span.start_child("db.query", "SELECT path, width, height FROM illusts WHERE id = ?");
        span.set_data("db:system", "mariadb".into());
        db_span = Some(span);
    }

    let image_info: Result<(String, Option<u64>, Option<u64>), sqlx::Error> = sqlx::query_as(
        "SELECT path, width, height FROM illusts WHERE id = ?")
        .bind(image_id)
        .fetch_one(&**pool).await;

    if db_span.is_some() {
        db_span.unwrap().finish();
    }

    if image_info.is_err() {
        match image_info {
            Err(sqlx::Error::RowNotFound) => {
                return HttpResponse::NotFound().into();
            },
            Err(ref e) => {
                sentry::capture_error(&e);
                return HttpResponse::InternalServerError().body("Failed to query DB");
            },
            _ => { /* Ok() */ },
        }
    }
    let image_info = image_info.unwrap();
    let image_path = image_info.0;
    let mut img_width: Option<u64> = image_info.1;
    let mut img_height: Option<u64> = image_info.2;

    if !path::Path::new(&image_path).is_file() {
        sentry::capture_message("Image ID exists in DB, but not found in real FS.", sentry::Level::Warning);
        return HttpResponse::NotFound().body("Image file not found.");
    }

    if resize_size.is_some() {
        let mut img: Option<image::DynamicImage> = None;

        if img_width.is_none() || img_height.is_none() {
            let img_try = read_image(&image_path);
            if img_try.is_err() {
                return img_try.unwrap_err();
            }
            let img_try = img_try.unwrap();
            img_width = Some(img_try.width() as u64);
            img_height = Some(img_try.height() as u64);
            img = Some(img_try);
        }

        let mut new_size: Option<(u32, u32)> = None;

        let img_width: f32 = img_width.unwrap() as f32;
        let img_height: f32 = img_height.unwrap() as f32;
        let resize_size = resize_size.unwrap();


        if img_width > img_height { // Check larger axis
            if img_width > resize_size { // Check should do resize
                let perc = resize_size / img_width;
                new_size = Some((resize_size as u32, (img_height * perc) as u32));
            }
        } else {
            if img_height > resize_size {
                let perc = resize_size / img_height;
                new_size = Some(((img_width * perc) as u32, resize_size as u32));
            }
        }

        if new_size.is_some() {
            let new_size = new_size.unwrap();

            if img.is_none() {
                let img_try = read_image(&image_path);
                if img_try.is_err() {
                    return img_try.unwrap_err();
                }
                img = Some(img_try.unwrap());
            }
            let img = img.unwrap();

            let mut resizer = fast_image_resize::Resizer::new();
            #[cfg(target_arch = "x86_64")]
            unsafe {
                resizer.set_cpu_extensions(fast_image_resize::CpuExtensions::Avx2);
            }

            let mut dst_image = Image::new(
                new_size.0,
                new_size.1,
                img.pixel_type().unwrap()
            );

            let resize_option = fast_image_resize::ResizeOptions::new();
            resize_option.resize_alg(fast_image_resize::ResizeAlg::Convolution(fast_image_resize::FilterType::Lanczos3));

            let resize_result = resizer.resize(&img, &mut dst_image, &resize_option);
            if resize_result.is_err() {
                sentry::capture_error(&resize_result.unwrap_err());
                return HttpResponse::InternalServerError().body("Failed to resize image");
            }

            let color_type: image::ExtendedColorType = img.color().into();

            let mut result_vec = Vec::new();
            let mut result_buf = io::Cursor::new(&mut result_vec);
            match color_type {
                image::ExtendedColorType::L8 |
                image::ExtendedColorType::Rgb8 => {
                    let encode_res = jpeg::JpegEncoder::new_with_quality(&mut result_buf, encode_quality)
                        .write_image(
                            dst_image.buffer(),
                            new_size.0,
                            new_size.1,
                            color_type,
                        );
        
                    if encode_res.is_err() {
                        sentry::capture_error(&encode_res.unwrap_err());
                        return HttpResponse::InternalServerError().body("Failed to encode image");
                    }
                },
                image::ExtendedColorType::La8 |
                image::ExtendedColorType::Rgba8 => {
                    let encode_res = webp::WebPEncoder::new_lossless(&mut result_buf)
                        .write_image(
                            dst_image.buffer(),
                            new_size.0,
                            new_size.1,
                            color_type,
                        );
        
                    if encode_res.is_err() {
                        sentry::capture_error(&encode_res.unwrap_err());
                        return HttpResponse::InternalServerError().body("Failed to encode image");
                    }
                },
                _ => {
                    let encode_res = png::PngEncoder::new(&mut result_buf)
                        .write_image(
                            dst_image.buffer(),
                            new_size.0,
                            new_size.1,
                            color_type,
                        );
        
                    if encode_res.is_err() {
                        sentry::capture_error(&encode_res.unwrap_err());
                        return HttpResponse::InternalServerError().body("Failed to encode image");
                    }
                },
            }

            return HttpResponse::Ok().body(result_vec);
        } else if image_path.ends_with(".lep") {
            // Not to resize.
            if img.is_some() {
                // To reduce processing time, re-encode to jpeg if already decoded lepton.
                let img = img.unwrap();
                let mut result_vec = Vec::new();
                let mut result_buf = io::Cursor::new(&mut result_vec);
                let encode_res = image::codecs::jpeg::JpegEncoder::new_with_quality(&mut result_buf, encode_quality)
                    .encode_image(
                        &img,
                    );
                if encode_res.is_err() {
                    sentry::capture_error(&encode_res.unwrap_err());
                    return HttpResponse::InternalServerError().body("Failed to encode image");
                }
                return HttpResponse::Ok().body(result_vec);
            } else {
                // If not, decode and return.
                return return_lepton(&image_path);
            }
        } else {
            // Not to resize, and re-encode. (Discard decoded result)
            return return_file(&request, &image_path).await;
        }
    } else if /* raw variant and */ image_path.ends_with(".lep") {
        return return_lepton(&image_path);
    } else /* raw variant and not lepton image */ {
        return return_file(&request, &image_path).await;
    }
}

#[tracing::instrument(skip_all)]
fn return_lepton(
    image_path: &str
) -> HttpResponse {
    let file = fs::File::options()
        .read(true)
        .write(false)
        .create(false)
        .append(false)
        .open(image_path);
    if file.is_err() {
        sentry::capture_error(&file.unwrap_err());
        return HttpResponse::InternalServerError().body("Unable to open file");
    }
    let file = file.unwrap();
    let mut reader = io::BufReader::new(file);

    let mut result_vec = Vec::new();
    let mut result_buf = io::Cursor::new(&mut result_vec);
    let decode_res = lepton_jpeg::decode_lepton(
        &mut reader,
        &mut result_buf,
        &lepton_jpeg::EnabledFeatures::compat_lepton_vector_read(),
        &lepton_jpeg::DEFAULT_THREAD_POOL
    );

    if decode_res.is_err() {
        sentry::capture_error(&decode_res.unwrap_err());
        return HttpResponse::InternalServerError().body("Failed to decode image");
    }

    return HttpResponse::Ok().body(result_vec);
}

#[tracing::instrument(skip_all)]
fn read_image(
    image_path: &str,
) -> Result<image::DynamicImage, HttpResponse> {
    let img_try: Result<_, io::Error> = ImageReader::open(&image_path);
    if img_try.is_err() {
        sentry::capture_message("Failed to open image file via ImageReader::open()", sentry::Level::Error);
        return Err(HttpResponse::InternalServerError().body("Failed to open image file"));
    }
    let img_try = img_try.unwrap().decode();
    if img_try.is_err() {
        sentry::capture_error(&img_try.unwrap_err());
        return Err(HttpResponse::InternalServerError().body("Failed to decode image file"));
    }

    Ok(img_try.unwrap())
}

async fn return_file(
    request: &HttpRequest,
    image_path: &str,
) -> HttpResponse {
        let file = actix_files::NamedFile::open_async(image_path).await;
        if file.is_err() {
            sentry::capture_error(&file.unwrap_err());
            return HttpResponse::InternalServerError().body("Unable to open file");
        }
        let file = file.unwrap();

        return file.into_response(&request);
}

fn main() {
    image_lepton::register();

    tracing_subscriber::Registry::default()
        //.with(tracing_subscriber::EnvFilter::from_default_env())
        .with(sentry::integrations::tracing::layer())
        .init();

    let _guard = sentry::init((
            env::var("SENTRY_DSN").unwrap_or("".to_string()),
            sentry::ClientOptions {
                release: sentry::release_name!(),
                // Capture all traces and spans. Set to a lower value in production
                traces_sample_rate: 1.0,
                // Capture user IPs and potentially sensitive headers when using HTTP server integrations
                // see https://docs.sentry.io/platforms/rust/data-management/data-collected for more info
                send_default_pii: false,
                // Capture all HTTP request bodies, regardless of size
                max_request_body_size: sentry::MaxRequestBodySize::Always,
                ..Default::default()
            },
    ));

    actix_web::rt::System::new().block_on(async {
        let pool = MySqlPoolOptions::new()
            .max_connections(5)
            .connect(
               &env::var("DB_DSN")
                    .unwrap_or("mysql://illustStore:illustStore@db/illustStore".to_string())
            ).await.expect("Unable to connect to DB");

        HttpServer::new(move || {
            App::new()
                .wrap(Logger::default())
                .wrap(
                    sentry::integrations::actix::Sentry::builder()
                    .capture_server_errors(true) // Capture server errors
                    .start_transaction(true) // Start a transaction (Sentry root span) for each request
                    .finish(),
                )
                .app_data(web::Data::new(pool.clone()))
                .service(get_image)
        })
        .bind("0.0.0.0:8080")?
            .run()
            .await
    }).expect("Failed to initialize Web Server");
}
