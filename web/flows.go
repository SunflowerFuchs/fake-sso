package web

import (
	_ "embed"
	"encoding/json"
	"github.com/SunflowerFuchs/fake-sso/identity"
	"html/template"
	"log"
	"net/http"
	"strings"
	"time"
)

type loginData struct {
	RedirectUri string
	State       string
}

type redirectData struct {
	Url  string
	Data []struct {
		Name  string
		Value any
	}
}

type accessTokenResponse struct {
	AccessToken string `json:"access_token"`
	TokenType   string `json:"token_type"`
	ExpiresIn   int    `json:"expires_in"`
}

type userInfoResponse struct {
	Id    string `json:"sub"`
	Name  string `json:"name,omitempty"`
	Email string `json:"email,omitempty"`
}

type errorResponse struct {
	Error string `json:"error"`
}

//go:embed templates/login.gohtml
var loginTemplate string

//go:embed templates/redirect.gohtml
var redirectTemplate string

func redirectWithData(data redirectData, w http.ResponseWriter) {
	tpl := template.New("redirect")
	tpl, _ = tpl.Parse(redirectTemplate)
	err := tpl.Execute(w, data)
	if err != nil {
		log.Fatal(err)
	}
}

func sendJsonError(msg string, code int, w http.ResponseWriter) {
	data := errorResponse{
		Error: msg,
	}
	w.WriteHeader(code)
	_ = json.NewEncoder(w).Encode(data)
}

func authorize(w http.ResponseWriter, r *http.Request) {
	if !r.URL.Query().Has("redirect_uri") {
		http.Error(w, "redirect_uri missing", 400)
		return
	}

	if !r.URL.Query().Has("response_type") ||
		!r.URL.Query().Has("redirect_uri") ||
		!r.URL.Query().Has("state") ||
		!r.URL.Query().Has("client_id") {
		redirectWithData(redirectData{
			Url: r.URL.Query().Get("redirect_uri"),
			Data: []struct {
				Name  string
				Value any
			}{
				{
					Name:  "error",
					Value: "invalid_request",
				},
			},
		}, w)
		return
	}

	if r.URL.Query().Get("response_type") != "code" {
		redirectWithData(redirectData{
			Url: r.URL.Query().Get("redirect_uri"),
			Data: []struct {
				Name  string
				Value any
			}{
				{
					Name:  "error",
					Value: "unsupported_response_type",
				},
			},
		}, w)
		return
	}

	data := loginData{
		RedirectUri: r.URL.Query().Get("redirect_uri"),
		State:       r.URL.Query().Get("state"),
	}

	tpl := template.New("login")
	tpl, _ = tpl.Parse(loginTemplate)
	err := tpl.Execute(w, data)
	if err != nil {
		log.Fatal(err)
	}
}

func submit(w http.ResponseWriter, r *http.Request) {
	_ = r.ParseForm()
	if !r.Form.Has("redirect_uri") ||
		!r.Form.Has("state") ||
		!r.Form.Has("email") ||
		len(strings.TrimSpace(r.Form.Get("email"))) == 0 {
		sendJsonError("invalid_request", 400, w)
		return
	}

	email := r.Form.Get("email")
	ident := identity.GetIdentityByEmail(email)
	code := ident.GetAuthCode()

	data := redirectData{
		Url: r.Form.Get("redirect_uri"),
		Data: []struct {
			Name  string
			Value any
		}{
			{
				Name:  "code",
				Value: code,
			},
			{
				Name:  "state",
				Value: r.Form.Get("state"),
			},
		},
	}

	redirectWithData(data, w)
}

func token(w http.ResponseWriter, r *http.Request) {
	_ = r.ParseForm()
	if !r.PostForm.Has("grant_type") ||
		!r.PostForm.Has("code") ||
		!r.PostForm.Has("client_id") ||
		!r.PostForm.Has("client_secret") {
		sendJsonError("invalid_request", 400, w)
		return
	}

	if r.PostForm.Get("grant_type") != "authorization_code" {
		sendJsonError("unsupported_grant_type", 400, w)
		return
	}

	code := r.PostForm.Get("code")
	ident, err := identity.FromAuthCode(code)
	if err != nil {
		sendJsonError("access_denied", 400, w)
		return
	}
	jwt, _ := ident.GetJwt()

	data := accessTokenResponse{
		AccessToken: jwt,
		TokenType:   "bearer",
		ExpiresIn:   int(5 * time.Minute / time.Second),
	}

	_ = json.NewEncoder(w).Encode(data)
}

func me(w http.ResponseWriter, r *http.Request) {
	auth := r.Header.Get("Authorization")
	if !strings.HasPrefix(auth, "Bearer ") {
		sendJsonError("invalid_token", 401, w)
		return
	}

	jwt := strings.TrimPrefix(auth, "Bearer ")
	ident, err := identity.FromJwt(jwt)
	if err != nil {
		sendJsonError("invalid_token", 401, w)
		return
	}

	data := userInfoResponse{
		Id:    ident.Id,
		Name:  ident.Name,
		Email: ident.Email,
	}
	_ = json.NewEncoder(w).Encode(data)
}

func SetupHandlers() {
	http.HandleFunc("/auth-code/authorize", authorize)
	http.HandleFunc("/auth-code/submit", submit)
	http.HandleFunc("/auth-code/token", token)
	http.HandleFunc("/auth-code/me", me)
}
