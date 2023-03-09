FROM golang:1.19-alpine

# For github container registry
LABEL org.opencontainers.image.source=https://github.com/sunflowerfuchs/fake-sso

RUN go build ./src/main.go

CMD ["./main"]
EXPOSE 80