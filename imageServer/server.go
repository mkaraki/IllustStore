package main

import (
	"bytes"
	"context"
	"database/sql"
	"fmt"
	"github.com/allegro/bigcache/v3"
	"github.com/davidbyttow/govips/v2/vips"
	"github.com/eko/gocache/lib/v4/cache"
	bigcache_store "github.com/eko/gocache/store/bigcache/v4"
	_ "github.com/go-sql-driver/mysql"
	"github.com/mkaraki/IllustStore/imageServer/lepton_jpeg"
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
	cachedImgContentType, cErr2 := strCacheManager.Get(cacheCtx, fmt.Sprintf("meta/img/%s/%d/Content-Type", variant, imId))

	if cErr1 == nil && cErr2 == nil && len(cachedImg) > 1 {
		w.Header().Set("Content-Type", cachedImgContentType)
		w.WriteHeader(http.StatusOK)
		_, _ = io.Copy(w, bytes.NewReader(cachedImg))
		return
	}

	// Prepare for raw image reading
	readBuff := &bytes.Buffer{}
	var contentType string

	// Check cache (raw level cache)
	cachedImg, cErr1 = byteCacheManager.Get(cacheCtx, fmt.Sprintf("data/img/raw/%d", imId))
	cachedImgContentType, cErr2 = strCacheManager.Get(cacheCtx, fmt.Sprintf("meta/img/raw/%d/Content-Type", imId))

	if cErr1 == nil && cErr2 == nil && len(cachedImg) > 0 {
		// if there are cached raw file
		_, _ = io.Copy(readBuff, bytes.NewReader(cachedImg))
		contentType = cachedImgContentType
	} else {
		// If there are no raw level cache,
		// read from disk
		db, err := sql.Open("mysql", "illustStore:illustStore@tcp(db:3306)/illustStore")
		if err != nil {
			w.WriteHeader(http.StatusInternalServerError)
			_, _ = w.Write([]byte("DB error"))
			fmt.Println(err)
			return
		}

		db.SetConnMaxLifetime(time.Minute * 3)
		db.SetMaxOpenConns(30)
		db.SetMaxIdleConns(30)

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
			err = lepton_jpeg.DecodeLepton(readBuff, fp)
			if err != nil {
				w.WriteHeader(http.StatusInternalServerError)
				_, _ = w.Write([]byte("Unable to read/decode file"))
				fmt.Println(err)
				return
			}
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
	_ = strCacheManager.Set(
		cacheCtx,
		fmt.Sprintf("meta/img/raw/%d/Content-Type", imId),
		contentType,
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

		err = imgRef.Resize(scale, vips.KernelLanczos2)
		if err != nil {
			w.WriteHeader(http.StatusInternalServerError)
			_, _ = w.Write([]byte("Failed to scale"))
			fmt.Println(err)
			return
		}

		exportParams := vips.NewWebpExportParams()
		webpBytes, _, err := imgRef.ExportWebp(exportParams)
		if err != nil {
			w.WriteHeader(http.StatusInternalServerError)
			_, _ = w.Write([]byte("Failed to write thumb data"))
			fmt.Println(err)
			return
		}

		_ = byteCacheManager.Set(
			cacheCtx,
			fmt.Sprintf("data/img/%s/%d", variant, imId),
			webpBytes,
		)
		_ = strCacheManager.Set(
			cacheCtx,
			fmt.Sprintf("meta/img/%s/%d/Content-Type", variant, imId),
			"image/webp",
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
var strCacheManager *cache.Cache[string]
var cacheCtx context.Context

func main() {
	vips.Startup(nil)
	defer vips.Shutdown()

	cacheCtx = context.Background()
	byteBigcacheClient, err := bigcache.New(cacheCtx, bigcache.DefaultConfig(1*time.Hour))
	if err != nil {
		log.Fatal(err)
	}
	byteBigcacheStore := bigcache_store.NewBigcache(byteBigcacheClient)

	strBigcacheClient, err := bigcache.New(cacheCtx, bigcache.DefaultConfig(1*time.Hour))
	if err != nil {
		log.Fatal(err)
	}
	strBigcacheStore := bigcache_store.NewBigcache(strBigcacheClient)

	byteCacheManager = cache.New[[]byte](byteBigcacheStore)
	strCacheManager = cache.New[string](strBigcacheStore)

	http.HandleFunc("/image/{imageId}/{variant}", imageFileHandler)

	fmt.Println("Starting server")
	err = http.ListenAndServe(":8080", nil)
	if err != nil {
		log.Fatal(err)
	}
}
