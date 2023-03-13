package main

import (
	"flag"
	"github.com/SunflowerFuchs/fake-sso/web"
)

func main() {
	port := flag.Int("port", 80, "Which port we should listen on")
	flag.Parse()

	web.Start(*port)
}
