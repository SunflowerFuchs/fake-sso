FROM golang:1.19-alpine

# For github container registry
LABEL org.opencontainers.image.source=https://github.com/sunflowerfuchs/fake-sso

ADD . /app
WORKDIR /app
RUN go build fake-sso.go

CMD ["./fake-sso"]
EXPOSE 80