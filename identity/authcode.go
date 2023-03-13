package identity

import (
	"github.com/google/uuid"
	"sync"
	"time"
)

type codeStorage struct {
	lock  sync.Mutex
	Codes map[string]string
}

var codeStore = &codeStorage{
	Codes: make(map[string]string),
}

func (identity *Identity) GetAuthCode() string {
	codeStore.lock.Lock()
	defer codeStore.lock.Unlock()

	code := uuid.NewString()
	codeStore.Codes[code] = identity.Id

	// Delete the code after 5 minutes
	t := time.NewTimer(5 * time.Minute)
	go func() {
		<-t.C
		codeStore.lock.Lock()
		defer codeStore.lock.Unlock()
		delete(codeStore.Codes, code)
	}()

	return code
}

func FromAuthCode(code string) (*Identity, error) {
	codeStore.lock.Lock()
	defer codeStore.lock.Unlock()

	id, exists := codeStore.Codes[code]
	if !exists {
		return nil, NotFoundError
	}
	delete(codeStore.Codes, code)

	identity, hasIdentity := db.Identities[id]
	if !hasIdentity {
		return nil, NotFoundError
	}

	return identity, nil
}
