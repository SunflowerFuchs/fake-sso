package web

import (
	"embed"
	"fmt"
	"html/template"
	"log"
	"net/http"
)

//go:embed all:static
var staticDirectory embed.FS

//go:embed templates/index.gohtml
var indexTemplate string

type indexTemplateVars struct {
	UrlPrefix string
}

func showIndex(w http.ResponseWriter, r *http.Request) {
	// Show regular 404 if we're not at the root
	if r.URL.Path != "/" {
		http.NotFound(w, r)
		return
	}

	// We have a nice little listing of all the flows and their routes here
	tpl := template.New("index")
	tpl, _ = tpl.Parse(indexTemplate)

	scheme := "http"
	if r.TLS != nil || r.Header.Get("X-Forwarded-Proto") == "https" {
		scheme = "https"
	}

	err := tpl.Execute(w, indexTemplateVars{
		UrlPrefix: fmt.Sprintf("%s://%s", scheme, r.Host),
	})
	if err != nil {
		log.Fatal(err)
	}
}

func Start(port int) {
	http.Handle("/static/", http.FileServer(http.FS(staticDirectory)))
	http.HandleFunc("/", showIndex)

	SetupHandlers()

	log.Printf("Starting server on http://localhost:%d...", port)
	log.Fatal(http.ListenAndServe(fmt.Sprintf(":%d", port), nil))
}
