package identity

import (
	"errors"
	"github.com/google/uuid"
	"sync"
	"syreclabs.com/go/faker"
)

type Identity struct {
	Id    string `json:"id"`
	Email string `json:"email"`
	Name  string `json:"name,omitempty"`
}

type storage struct {
	lock       sync.Mutex
	Identities map[string]*Identity `json:"identities"`
}

var (
	db = storage{
		Identities: make(map[string]*Identity),
	}
	NotFoundError = errors.New("user not found")
)

func GetIdentityByEmail(email string) *Identity {
	db.lock.Lock()
	defer db.lock.Unlock()

	for _, identity := range db.Identities {
		if identity.Email == email {
			return identity
		}
	}

	newIdentity := generateIdentity()
	newIdentity.Email = email
	db.Identities[newIdentity.Id] = newIdentity
	return newIdentity
}

func generateIdentity() *Identity {
	return &Identity{
		Id:    uuid.NewString(),
		Email: faker.Internet().SafeEmail(),
		Name:  faker.Name().Name(),
	}
}
