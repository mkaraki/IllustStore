package main

import (
	"bytes"
	"context"
	"database/sql"
	"fmt"
	"github.com/bradfitz/gomemcache/memcache"
	"github.com/davidbyttow/govips/v2/vips"
	"github.com/eko/gocache/lib/v4/cache"
	"github.com/eko/gocache/lib/v4/store"
	memcache_store "github.com/eko/gocache/store/memcache/v4"
	_ "github.com/go-sql-driver/mysql"
	"github.com/mkaraki/IllustStore/imageServer/lepton_jpeg"
	"github.com/prometheus/client_golang/prometheus"
	"github.com/prometheus/client_golang/prometheus/promauto"
	"github.com/prometheus/client_golang/prometheus/promhttp"
	"io"
	"log"
	"net/http"
	"os"
	"strconv"
	"strings"
	"time"
)

func isSupportedImage(requestExtension string) bool {
	switch requestExtension {
	case "png":
		return true
	case "lep":
		return true
	case "jpg", "jpeg":
		return true
	case "gif":
		return true
	case "avif":
		return true
	case "webp":
		return true
	default:
		return false
	}
}

func getExtensionFromFilePath(queryFile string) string {
	requestExtensionAry := strings.Split(queryFile, ".")
	return strings.ToLower(requestExtensionAry[len(requestExtensionAry)-1])
}

func getContentTypeFromExtension(requestExtension string) string {
	var contentType string

	switch requestExtension {
	case "png":
		contentType = "image/png"
	case "lep":
		contentType = "image/jpeg"
	case "jpg", "jpeg":
		contentType = "image/jpeg"
	case "gif":
		contentType = "image/gif"
	case "avif":
		contentType = "image/avif"
	case "webp":
		contentType = "image/webp"
	}

	return contentType
}

var imgCacheHitRate float64 = 0.0
var imgCacheHitRateProm = promauto.NewGauge(prometheus.GaugeOpts{
	Name: "image_cache_hit_rate",
	Help: "Variant level image cache hit rate",
})
var imgRawCacheHitRate float64 = 0.0
var imgRawCacheHitRateProm prometheus.Gauge = promauto.NewGauge(prometheus.GaugeOpts{
	Name: "image_raw_cache_hit_rate",
	Help: "Raw image cache hit rate",
})

var leptonProcessingAverageMilliSeconds float64 = 0.0
var leptonProcessingAverageMilliSecondsProm = promauto.NewGauge(prometheus.GaugeOpts{
	Name: "lepton_processing_average_milli_seconds",
	Help: "Average milli-second time for decode lepton image",
})
var resizeProcessingAverageMilliSeconds float64 = 0.0
var resizeProcessingAverageMilliSecondsProm = promauto.NewGauge(prometheus.GaugeOpts{
	Name: "resize_processing_average_milli_seconds",
	Help: "Average milli-second time for resizing image",
})
var encodeResizedProcessingAverageMilliSeconds float64 = 0.0
var encodeResizedProcessingAverageMilliSecondsProm = promauto.NewGauge(prometheus.GaugeOpts{
	Name: "encode_resized_processing_average_milli_seconds",
	Help: "Average milli-second time for encode resized image",
})

var db *sql.DB

func openDb() error {
	var err error
	if db != nil {
		_ = db.Close()
	}
	db, err = sql.Open("mysql", "illustStore:illustStore@tcp(db:3306)/illustStore")
	if err != nil {
		return err
	}

	db.SetConnMaxLifetime(time.Minute * 3)
	db.SetMaxOpenConns(10)
	db.SetMaxIdleConns(10)

	return nil
}

func useDb() error {
	if db == nil {
		err := openDb()
		if err != nil {
			return err
		}
	}

	if db.Ping() != nil {
		err := openDb()
		if err != nil {
			return err
		}
	}

	return nil
}

func imageFileHandler(w http.ResponseWriter, r *http.Request) {
	variant := r.PathValue("variant")

	var do_resize bool
	var resize_size int
	var resize_size_f float64

	switch variant {
	case "raw":
		do_resize = false
	case "large":
		do_resize = true
		resize_size = 1920
		resize_size_f = 1920.0
	case "thumb":
		do_resize = true
		resize_size = 250
		resize_size_f = 250.0
	default:
		w.WriteHeader(http.StatusBadRequest)
		_, _ = w.Write([]byte("Unsupported variant"))
		return
	}

	imageId := r.PathValue("imageId")
	imId, err := strconv.Atoi(imageId)

	if err != nil {
		w.WriteHeader(http.StatusBadRequest)
		_, _ = w.Write([]byte("imageId is invalid."))
		return
	}

	// Check cache (variant level cache)
	cachedImg, cErr1 := byteCacheManager.Get(cacheCtx, fmt.Sprintf("data/img/%s/%d", variant, imId))
	cachedImgContentType, cErr2 := byteCacheManager.Get(cacheCtx, fmt.Sprintf("meta/img/%s/%d/Content-Type", variant, imId))

	if cErr1 == nil && cErr2 == nil && len(cachedImg) > 1 {
		w.Header().Set("Content-Type", string(cachedImgContentType))
		w.WriteHeader(http.StatusOK)
		_, _ = io.Copy(w, bytes.NewReader(cachedImg))

		imgCacheHitRate += 1.0
		imgCacheHitRate /= 2.0
		imgCacheHitRateProm.Set(imgCacheHitRate)

		return
	}

	imgCacheHitRate += 0.0
	imgCacheHitRate /= 2.0
	imgCacheHitRateProm.Set(imgCacheHitRate)

	// Prepare for raw image reading
	readBuff := &bytes.Buffer{}
	var contentType string

	// Check cache (raw level cache)
	cachedImg, cErr1 = byteCacheManager.Get(cacheCtx, fmt.Sprintf("data/img/raw/%d", imId))
	cachedImgContentType, cErr2 = byteCacheManager.Get(cacheCtx, fmt.Sprintf("meta/img/raw/%d/Content-Type", imId))

	if cErr1 == nil && cErr2 == nil && len(cachedImg) > 0 {
		// if there are cached raw file
		_, _ = io.Copy(readBuff, bytes.NewReader(cachedImg))
		contentType = string(cachedImgContentType)

		imgRawCacheHitRate += 1.0
		imgRawCacheHitRate /= 2.0
		imgRawCacheHitRateProm.Set(imgRawCacheHitRate)
	} else {
		// If there are no raw level cache,
		// read from disk

		imgRawCacheHitRate += 0.0
		imgRawCacheHitRate /= 2.0
		imgRawCacheHitRateProm.Set(imgRawCacheHitRate)

		err = useDb()
		if err != nil {
			w.WriteHeader(http.StatusInternalServerError)
			_, _ = w.Write([]byte("Db open fail"))
			fmt.Println(err)
			return
		}

		var path string
		err = db.QueryRow("SELECT i.path FROM illusts i WHERE i.id = ?", imId).Scan(&path)
		if err != nil {
			w.WriteHeader(http.StatusNotFound)
			_, _ = w.Write([]byte("Image not found or DB error"))
			fmt.Println(err)
			return
		}

		imgExt := getExtensionFromFilePath(path)

		if !isSupportedImage(imgExt) {
			w.WriteHeader(http.StatusBadRequest)
			_, _ = w.Write([]byte("Unsupported image"))
			return
		}

		contentType = getContentTypeFromExtension(imgExt)

		fp, err := os.OpenFile(path, os.O_RDONLY, 0666)
		if err != nil {
			w.WriteHeader(http.StatusInternalServerError)
			_, _ = w.Write([]byte("Unable to open file."))
			fmt.Println(err)
			return
		}

		switch imgExt {
		case "lep":
			startTime := time.Now()
			err = lepton_jpeg.DecodeLepton(readBuff, fp)
			if err != nil {
				w.WriteHeader(http.StatusInternalServerError)
				_, _ = w.Write([]byte("Unable to read/decode file"))
				fmt.Println(err)
				return
			}
			leptonProcessingAverageMilliSeconds += float64(time.Now().Sub(startTime).Milliseconds())
			leptonProcessingAverageMilliSeconds /= 2.0
			leptonProcessingAverageMilliSecondsProm.Set(leptonProcessingAverageMilliSeconds)
		default:
			_, err = io.Copy(readBuff, fp)
			if err != nil {
				w.WriteHeader(http.StatusInternalServerError)
				_, _ = w.Write([]byte("Unable to read file"))
				fmt.Println(err)
				return
			}
		}
	}

	// This section is after read raw image.

	// cache read image
	_ = byteCacheManager.Set(
		cacheCtx,
		fmt.Sprintf("data/img/raw/%d", imId),
		readBuff.Bytes(),
	)
	_ = byteCacheManager.Set(
		cacheCtx,
		fmt.Sprintf("meta/img/raw/%d/Content-Type", imId),
		[]byte(contentType),
	)

	if do_resize {
		// Resize
		imgRef, err := vips.NewImageFromReader(readBuff)
		if err != nil {
			w.WriteHeader(http.StatusInternalServerError)
			_, _ = w.Write([]byte("Unable to read image"))
			fmt.Println(err)
			return
		}

		origWidth := imgRef.Width()
		origHeight := imgRef.Height()

		if origWidth <= resize_size && origHeight <= resize_size {
			// No resize return.
			w.Header().Set("Content-Type", contentType)
			w.WriteHeader(http.StatusOK)
			_, err = io.Copy(w, readBuff)
			if err != nil {
				return
			}
		}

		var scale float64

		if origWidth > origHeight {
			scale = resize_size_f / float64(origWidth)
		} else {
			scale = resize_size_f / float64(origHeight)
		}

		startTime := time.Now()
		err = imgRef.Resize(scale, vips.KernelLanczos2)
		if err != nil {
			w.WriteHeader(http.StatusInternalServerError)
			_, _ = w.Write([]byte("Failed to scale"))
			fmt.Println(err)
			return
		}
		resizeProcessingAverageMilliSeconds += float64(time.Now().Sub(startTime).Milliseconds())
		resizeProcessingAverageMilliSeconds /= 2.0
		resizeProcessingAverageMilliSecondsProm.Set(resizeProcessingAverageMilliSeconds)

		exportParams := vips.NewWebpExportParams()
		startTime = time.Now()
		webpBytes, _, err := imgRef.ExportWebp(exportParams)
		if err != nil {
			w.WriteHeader(http.StatusInternalServerError)
			_, _ = w.Write([]byte("Failed to write thumb data"))
			fmt.Println(err)
			return
		}
		encodeResizedProcessingAverageMilliSeconds += float64(time.Now().Sub(startTime).Milliseconds())
		encodeResizedProcessingAverageMilliSeconds /= 2.0
		encodeResizedProcessingAverageMilliSecondsProm.Set(encodeResizedProcessingAverageMilliSeconds)

		_ = byteCacheManager.Set(
			cacheCtx,
			fmt.Sprintf("data/img/%s/%d", variant, imId),
			webpBytes,
		)
		_ = byteCacheManager.Set(
			cacheCtx,
			fmt.Sprintf("meta/img/%s/%d/Content-Type", variant, imId),
			[]byte("image/webp"),
		)

		w.Header().Set("Content-Type", "image/webp")
		w.WriteHeader(http.StatusOK)
		_, err = io.Copy(w, bytes.NewBuffer(webpBytes))
		if err != nil {
			return
		}
	} else {
		// No resize (raw) return.
		w.Header().Set("Content-Type", contentType)
		w.WriteHeader(http.StatusOK)
		_, err = io.Copy(w, readBuff)
		if err != nil {
			return
		}
	}
}

var byteCacheManager *cache.Cache[[]byte]
var cacheCtx context.Context

func main() {
	vips.Startup(nil)
	defer vips.Shutdown()

	byteMemcacheStore := memcache_store.NewMemcache(
		memcache.New("memcached:11211"),
		store.WithExpiration(2*time.Hour),
	)

	byteCacheManager = cache.New[[]byte](byteMemcacheStore)

	http.HandleFunc("/image/{imageId}/{variant}", imageFileHandler)
	http.Handle("/metrics", promhttp.Handler())

	fmt.Println("Starting server")
	err := http.ListenAndServe(":8080", nil)
	if err != nil {
		log.Fatal(err)
	}
}
