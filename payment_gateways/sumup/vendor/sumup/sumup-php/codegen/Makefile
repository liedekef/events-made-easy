# Make this makefile self-documented with target `help`
.PHONY: help
.DEFAULT_GOAL := help
help: ## Show help
	@grep -Eh '^[0-9a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: fmt
fmt: ## Format go files
	golangci-lint fmt -v

.PHONY: lint
lint: ## Lint go files
	golangci-lint run -v

.PHONY: test
test: ## Run tests
	go test -v -failfast -race -timeout 10m ./...

.PHONY: download
download:
	@echo Download go.mod dependencies
	@go mod download

.PHONE: vulncheck
vulncheck: ## Check for Vulnerabilities (make sure you have the tools install: `make install-tools`)
	govulncheck ./...

.PHONY: install
install: ## Install latest version of py-sdk-gen
	go install ./cmd/py-sdk-gen/...

.PHONY: generate
generate: ## Generate all test files
	@set -e
	find ./tests -type f -name 'go.mod' -execdir sh -c 'go generate ./...' \;

.PHONY: update-examples
update-examples: ## Update all examples
	@set -e
	find ./examples -type f -name 'Makefile' -execdir sh -c 'make' \;
