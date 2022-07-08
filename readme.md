# FakeSSO

## Description

This repo is just a quick SSO provider mock.

## Installation

1. Copy the .env.example to .env
2. Change variables as needed
3. Run `docker-compose up -d`
4. Profit

## Development

The container has 2 build stages, just build the image with `--target dev` to generate the development image. 

The development image has xdebug added for easy debugging, and composer for installing dev-dependencies like phpunit.