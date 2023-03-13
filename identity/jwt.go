package identity

import (
	"errors"
	"github.com/dgrijalva/jwt-go/v4"
	"golang.org/x/xerrors"
	"time"
)

type identityClaims struct {
	IdentityId string `json:"identity_id"`
	jwt.StandardClaims
}

var signingKey = []byte("Secret")

func (identity *Identity) GetJwt() (string, error) {
	token := jwt.NewWithClaims(jwt.SigningMethodHS256, identityClaims{
		IdentityId: identity.Id,
		StandardClaims: jwt.StandardClaims{
			ExpiresAt: jwt.NewTime(float64(time.Now().Add(5 * time.Minute).Unix())),
			Issuer:    "fake-sso",
		},
	})

	return token.SignedString(signingKey)
}

func FromJwt(tokenString string) (*Identity, error) {
	token, err := jwt.ParseWithClaims(tokenString, &identityClaims{}, func(token *jwt.Token) (interface{}, error) {
		return signingKey, nil
	})

	if claims, ok := token.Claims.(*identityClaims); ok && token.Valid {
		identity, exists := db.Identities[claims.IdentityId]
		if !exists {
			return nil, NotFoundError
		}
		return identity, nil
	} else if xerrors.As(err, jwt.UnverfiableTokenError{}) {
		return nil, errors.New("could not read token")
	} else if xerrors.As(err, jwt.TokenExpiredError{}) {
		return nil, errors.New("token is expired")
	} else {
		return nil, errors.New("unexpected error")
	}
}
