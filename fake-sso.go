package main

import (
	"embed"
	"fmt"
	"html/template"
	"log"
	"net/http"
)

//go:embed all:web/static
var staticDirectory embed.FS

//go:embed all:web/templates
var templateDirectory embed.FS

type indexTemplateVars struct {
	UrlPrefix string
}

func main() {
	http.HandleFunc("/", func(w http.ResponseWriter, r *http.Request) {
		tpl := template.New("index")
		tplString, err := templateDirectory.ReadFile("web/templates/index.gohtml")
		if err != nil {
			log.Fatal(err)
		}
		tpl, err = tpl.Parse(string(tplString))

		scheme := "http"
		if r.TLS != nil {
			scheme = "https"
		}

		err = tpl.Execute(w, indexTemplateVars{
			UrlPrefix: fmt.Sprintf("%s://%s", scheme, r.Host),
		})
		if err != nil {
			log.Fatal(err)
		}
	})
	http.Handle("/web/static/", http.FileServer(http.FS(staticDirectory)))

	log.Println("Starting server on http://localhost:80...")
	log.Fatal(http.ListenAndServe(":80", nil))
}
