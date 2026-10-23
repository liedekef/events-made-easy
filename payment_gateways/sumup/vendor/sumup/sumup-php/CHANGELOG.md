# Changelog

## [0.1.7](https://github.com/sumup/sumup-php/compare/v0.1.6...v0.1.7) (2026-10-15)


### Bug Fixes

* codesamples sync ([96f52c8](https://github.com/sumup/sumup-php/commit/96f52c8419f31308a852f57f73c49bcb08e2d3b1))
* preserve annotated schemas and handle Guzzle errors ([#129](https://github.com/sumup/sumup-php/issues/129)) ([cae664e](https://github.com/sumup/sumup-php/commit/cae664e942a7f0c8741b072f3cc612f55c3e9d6d))


### Chores

* **codegen:** nullable schemas with 3.1.0 specs ([78659ab](https://github.com/sumup/sumup-php/commit/78659ab0f64201cd0fa8997ef9aa659306ae20e1))
* synced file(s) with sumup/apis ([#114](https://github.com/sumup/sumup-php/issues/114)) ([caebecc](https://github.com/sumup/sumup-php/commit/caebecca695960f42ed575f43f2510a3637f3bce))
* synced file(s) with sumup/apis ([#119](https://github.com/sumup/sumup-php/issues/119)) ([2bcf9d4](https://github.com/sumup/sumup-php/commit/2bcf9d4fae820c4ae44d7e8044bd0336b0e633f3))
* use draft releases ([e83e9d9](https://github.com/sumup/sumup-php/commit/e83e9d9a6879c764b0806a4a2df01995eaa16825))


### Build System & Dependencies

* **deps-dev:** update friendsofphp/php-cs-fixer requirement from 3.95.18 to 3.95.19 ([#111](https://github.com/sumup/sumup-php/issues/111)) ([82f343c](https://github.com/sumup/sumup-php/commit/82f343c71bf416d33489ba9199abfc52caa71796))
* **deps-dev:** update friendsofphp/php-cs-fixer requirement from 3.95.19 to 3.95.24 ([#116](https://github.com/sumup/sumup-php/issues/116)) ([a1f394f](https://github.com/sumup/sumup-php/commit/a1f394f38ffe73a76470c227f6a32fee05e2b02a))
* **deps-dev:** update friendsofphp/php-cs-fixer requirement from 3.95.24 to 3.95.27 ([#122](https://github.com/sumup/sumup-php/issues/122)) ([e7bae88](https://github.com/sumup/sumup-php/commit/e7bae887de1e3697b6cfe7cbb2c280103c821e55))
* **deps:** bump github.com/lmittmann/tint from 1.2.0 to 1.2.1 in /codegen ([#126](https://github.com/sumup/sumup-php/issues/126)) ([11699a2](https://github.com/sumup/sumup-php/commit/11699a28ff2e9bf3a726bb16470ce77f675c2de6))
* **deps:** bump github.com/pb33f/libopenapi from 0.38.7 to 0.41.3 in /codegen ([#125](https://github.com/sumup/sumup-php/issues/125)) ([5f50a7e](https://github.com/sumup/sumup-php/commit/5f50a7e31201f72456c8881f86549492154cd0fc))
* **deps:** bump reviewdog/action-actionlint from 1.73.1 to 1.73.2 ([#112](https://github.com/sumup/sumup-php/issues/112)) ([d124dfc](https://github.com/sumup/sumup-php/commit/d124dfc647f38028b9d5f416b21183bd6c7aaf8b))
* **deps:** bump reviewdog/action-actionlint from 1.73.2 to 1.73.4 ([#115](https://github.com/sumup/sumup-php/issues/115)) ([b7a920a](https://github.com/sumup/sumup-php/commit/b7a920af4a7b203223f04e0fac26a3e57e36e719))
* **deps:** bump reviewdog/action-actionlint from 1.73.4 to 1.79.1 ([#124](https://github.com/sumup/sumup-php/issues/124)) ([6d6efb2](https://github.com/sumup/sumup-php/commit/6d6efb2541665e1c36dcf4811c1dd2533dc20da2))

## [0.1.6](https://github.com/sumup/sumup-php/compare/v0.1.5...v0.1.6) (2026-08-19)


### Features

* improve code samples generation ([87331be](https://github.com/sumup/sumup-php/commit/87331be2ab19d765e39140758105f49039952e22))


### Chores

* fix tests for sample generator ([d95623b](https://github.com/sumup/sumup-php/commit/d95623b5cae92d57bf9eeb4080901811e042a628))
* synced file(s) with sumup/apis ([#106](https://github.com/sumup/sumup-php/issues/106)) ([a9b4b5a](https://github.com/sumup/sumup-php/commit/a9b4b5a8f419180ee679a0787e6ba2ca58863368))
* synced file(s) with sumup/apis ([#109](https://github.com/sumup/sumup-php/issues/109)) ([08b1473](https://github.com/sumup/sumup-php/commit/08b14736a78d650c2e030baa254baf5937bd8a92))


### Build System & Dependencies

* **deps:** bump reviewdog/action-actionlint from 1.73.0 to 1.73.1 ([#108](https://github.com/sumup/sumup-php/issues/108)) ([37a22be](https://github.com/sumup/sumup-php/commit/37a22be89c6cc16b5830abd383659fa51d2ea39d))

## [0.1.5](https://github.com/sumup/sumup-php/compare/v0.1.4...v0.1.5) (2026-08-12)


### Features

* **codegen:** generate validated PHP code samples ([#101](https://github.com/sumup/sumup-php/issues/101)) ([fc227c8](https://github.com/sumup/sumup-php/commit/fc227c8a1cd44af506814368ab72faa7d23fcf17))
* issue templates ([#62](https://github.com/sumup/sumup-php/issues/62)) ([3ca090f](https://github.com/sumup/sumup-php/commit/3ca090f143d34a86b4bb26964f1255b97349c367))


### Chores

* **cd:** don't run release please on release ([#89](https://github.com/sumup/sumup-php/issues/89)) ([62c24d3](https://github.com/sumup/sumup-php/commit/62c24d3966f843522f0ee851aab3f5ae7f6b7792))
* remove deprecated `curl_close` ([#97](https://github.com/sumup/sumup-php/issues/97)) ([5cae736](https://github.com/sumup/sumup-php/commit/5cae73666785aaf0ab27aa5a880f9d129572c9ef))
* synced file(s) with sumup/apis ([#61](https://github.com/sumup/sumup-php/issues/61)) ([80bdc7f](https://github.com/sumup/sumup-php/commit/80bdc7f6caa906a8a4391e5aad638ca601e5fcbe))
* synced file(s) with sumup/apis ([#64](https://github.com/sumup/sumup-php/issues/64)) ([fc7ff8b](https://github.com/sumup/sumup-php/commit/fc7ff8b1df001d0cec461e83407216c3d2e15a41))
* synced file(s) with sumup/apis ([#67](https://github.com/sumup/sumup-php/issues/67)) ([6cd9083](https://github.com/sumup/sumup-php/commit/6cd9083466cdc9a071d077be59aa09236ac1e917))
* synced file(s) with sumup/apis ([#86](https://github.com/sumup/sumup-php/issues/86)) ([96e409b](https://github.com/sumup/sumup-php/commit/96e409b42aaa23ddd5e2e964912d7b82f60a05fc))
* synced file(s) with sumup/apis ([#90](https://github.com/sumup/sumup-php/issues/90)) ([354bc74](https://github.com/sumup/sumup-php/commit/354bc74470a03718c722661fdedd645b59464d9d))
* synced file(s) with sumup/apis ([#95](https://github.com/sumup/sumup-php/issues/95)) ([a6e860d](https://github.com/sumup/sumup-php/commit/a6e860de6cb83a02c343554a668138c8d13efa3c))


### Build System & Dependencies

* **deps-dev:** update friendsofphp/php-cs-fixer requirement from 3.95.1 to 3.95.2 ([#59](https://github.com/sumup/sumup-php/issues/59)) ([9e78497](https://github.com/sumup/sumup-php/commit/9e78497815caa3d4120698efc051ac64a4e9878c))
* **deps-dev:** update friendsofphp/php-cs-fixer requirement from 3.95.11 to 3.95.13 ([#88](https://github.com/sumup/sumup-php/issues/88)) ([a13ca8f](https://github.com/sumup/sumup-php/commit/a13ca8ff2312041262d893b42680c6e7d93e9762))
* **deps-dev:** update friendsofphp/php-cs-fixer requirement from 3.95.13 to 3.95.15 ([#93](https://github.com/sumup/sumup-php/issues/93)) ([f9849cc](https://github.com/sumup/sumup-php/commit/f9849cc40160fb7f4c7687a55ae048e7fba7f7dd))
* **deps-dev:** update friendsofphp/php-cs-fixer requirement from 3.95.15 to 3.95.17 ([#98](https://github.com/sumup/sumup-php/issues/98)) ([4807e14](https://github.com/sumup/sumup-php/commit/4807e143831443464e6fdf4781a3587422771749))
* **deps-dev:** update friendsofphp/php-cs-fixer requirement from 3.95.17 to 3.95.18 ([#105](https://github.com/sumup/sumup-php/issues/105)) ([c669aee](https://github.com/sumup/sumup-php/commit/c669aee99a7471f6a33b338cb119c31e59e3df36))
* **deps-dev:** update friendsofphp/php-cs-fixer requirement from 3.95.2 to 3.95.5 ([#75](https://github.com/sumup/sumup-php/issues/75)) ([549620c](https://github.com/sumup/sumup-php/commit/549620c2f4af9bc97d61a0ef1aa2b71702f47974))
* **deps-dev:** update friendsofphp/php-cs-fixer requirement from 3.95.5 to 3.95.11 ([#83](https://github.com/sumup/sumup-php/issues/83)) ([a23c0ec](https://github.com/sumup/sumup-php/commit/a23c0ece50acd023da724dd033905f3622f03ace))
* **deps:** bump actions/checkout from 6.0.2 to 6.0.3 ([#70](https://github.com/sumup/sumup-php/issues/70)) ([3b46147](https://github.com/sumup/sumup-php/commit/3b46147c3483f10b01a385f762c8a2b2a447e80f))
* **deps:** bump actions/checkout from 6.0.2 to 7.0.1 ([#102](https://github.com/sumup/sumup-php/issues/102)) ([204ddde](https://github.com/sumup/sumup-php/commit/204ddde0e103713357586aa0fb5559d05a8e8ce2))
* **deps:** bump actions/checkout from 6.0.3 to 7.0.0 ([#77](https://github.com/sumup/sumup-php/issues/77)) ([0d85c16](https://github.com/sumup/sumup-php/commit/0d85c1620ab25768c83685456aaeb11834c62268))
* **deps:** bump actions/checkout from 7.0.0 to 7.0.1 ([#99](https://github.com/sumup/sumup-php/issues/99)) ([964e7d7](https://github.com/sumup/sumup-php/commit/964e7d7c5e576314a2026e6229b9b34efef81bd7))
* **deps:** bump actions/create-github-app-token from 3.1.1 to 3.2.0 ([#103](https://github.com/sumup/sumup-php/issues/103)) ([dc78570](https://github.com/sumup/sumup-php/commit/dc78570ba59ca13549e55be437936ede861952ce))
* **deps:** bump actions/create-github-app-token from 3.1.1 to 3.2.0 ([#57](https://github.com/sumup/sumup-php/issues/57)) ([eb329b8](https://github.com/sumup/sumup-php/commit/eb329b8b56b2d0c5be6b42ae947e7acc37e6620f))
* **deps:** bump actions/setup-go from 6.4.0 to 6.5.0 ([#82](https://github.com/sumup/sumup-php/issues/82)) ([117b927](https://github.com/sumup/sumup-php/commit/117b927c756d38beb6e316f8e7dce778bf832646))
* **deps:** bump actions/setup-go from 6.4.0 to 7.0.0 ([#104](https://github.com/sumup/sumup-php/issues/104)) ([a4fa046](https://github.com/sumup/sumup-php/commit/a4fa0463dbd7ba8d29a7712c27f97de8a51af74a))
* **deps:** bump actions/setup-go from 6.5.0 to 7.0.0 ([#91](https://github.com/sumup/sumup-php/issues/91)) ([a98f2c1](https://github.com/sumup/sumup-php/commit/a98f2c172901fb31e4fdc8f5b4307966899003bf))
* **deps:** bump github.com/lmittmann/tint from 1.1.3 to 1.2.0 in /codegen ([#94](https://github.com/sumup/sumup-php/issues/94)) ([580ff48](https://github.com/sumup/sumup-php/commit/580ff48bd6f7aa29fe3fdc4b43064624bf6813e2))
* **deps:** bump github.com/pb33f/libopenapi from 0.36.3 to 0.36.4 in /codegen ([#60](https://github.com/sumup/sumup-php/issues/60)) ([901fe55](https://github.com/sumup/sumup-php/commit/901fe55c9b16ca4839ecfdfc39c690591e6fc680))
* **deps:** bump github.com/pb33f/libopenapi from 0.36.4 to 0.37.3 in /codegen ([#72](https://github.com/sumup/sumup-php/issues/72)) ([e3c0575](https://github.com/sumup/sumup-php/commit/e3c0575a210071078028321c09153965338bc59b))
* **deps:** bump github.com/pb33f/libopenapi from 0.37.3 to 0.38.3 in /codegen ([#81](https://github.com/sumup/sumup-php/issues/81)) ([e9bda71](https://github.com/sumup/sumup-php/commit/e9bda71ce26e22f75b93144578b0374e0f4cf56c))
* **deps:** bump github.com/pb33f/libopenapi from 0.38.3 to 0.38.5 in /codegen ([#84](https://github.com/sumup/sumup-php/issues/84)) ([c95e1b9](https://github.com/sumup/sumup-php/commit/c95e1b9e1d023c9acf66cf804f82784e1ff0114a))
* **deps:** bump github.com/pb33f/libopenapi from 0.38.5 to 0.38.6 in /codegen ([#87](https://github.com/sumup/sumup-php/issues/87)) ([f4fe4b5](https://github.com/sumup/sumup-php/commit/f4fe4b5bcc5db1d0f60be5c23082af29d41fde54))
* **deps:** bump github.com/pb33f/libopenapi from 0.38.6 to 0.38.7 in /codegen ([#92](https://github.com/sumup/sumup-php/issues/92)) ([1cbdd28](https://github.com/sumup/sumup-php/commit/1cbdd2812f301a9ef61acb5d95882c409805b42c))
* **deps:** bump go.yaml.in/yaml/v4 from 4.0.0-rc.4 to 4.0.0-rc.5 in /codegen ([#74](https://github.com/sumup/sumup-php/issues/74)) ([c29658e](https://github.com/sumup/sumup-php/commit/c29658e84331bd2e0374cd7df4fc20da65dc11d6))
* **deps:** bump golangci/golangci-lint-action from 9.2.0 to 9.2.1 ([#65](https://github.com/sumup/sumup-php/issues/65)) ([e9cd86f](https://github.com/sumup/sumup-php/commit/e9cd86f89ce55db6a38dd0c26cdacd25a34d17b9))
* **deps:** bump golangci/golangci-lint-action from 9.2.1 to 9.3.0 ([#85](https://github.com/sumup/sumup-php/issues/85)) ([ea880a6](https://github.com/sumup/sumup-php/commit/ea880a691134aa1a12ac4ec228243243e71fe589))
* **deps:** bump reviewdog/action-actionlint from 1.72.0 to 1.73.0 ([#100](https://github.com/sumup/sumup-php/issues/100)) ([1d82451](https://github.com/sumup/sumup-php/commit/1d82451dfe7edd607d0df96172d5efd95292a09a))
* **deps:** bump shivammathur/setup-php from 2.37.0 to 2.37.1 ([#58](https://github.com/sumup/sumup-php/issues/58)) ([3258ec6](https://github.com/sumup/sumup-php/commit/3258ec6844869a6ef37cfd152671e0471f1464eb))
* **deps:** bump shivammathur/setup-php from 2.37.1 to 2.37.2 ([#73](https://github.com/sumup/sumup-php/issues/73)) ([63cb830](https://github.com/sumup/sumup-php/commit/63cb8304da9ae729e45dd2285b654bd962da7666))

## [0.1.4](https://github.com/sumup/sumup-php/compare/v0.1.3...v0.1.4) (2026-05-14)


### Features

* **sdk:** allow sending arbitrary request ([#56](https://github.com/sumup/sumup-php/issues/56)) ([055ea0f](https://github.com/sumup/sumup-php/commit/055ea0f3487193841271e6e3eccf0ef4da5b72f0))


### Chores

* synced file(s) with sumup/apis ([#51](https://github.com/sumup/sumup-php/issues/51)) ([713cec2](https://github.com/sumup/sumup-php/commit/713cec29ed6c5f5100a1f8f49d01b32d663ae7cc))
* synced file(s) with sumup/apis ([#54](https://github.com/sumup/sumup-php/issues/54)) ([c942e8b](https://github.com/sumup/sumup-php/commit/c942e8b2f8849e00c29682444f3fad9742c63eff))


### Build System & Dependencies

* **deps-dev:** update friendsofphp/php-cs-fixer requirement from 3.94.2 to 3.95.1 ([#52](https://github.com/sumup/sumup-php/issues/52)) ([14fea98](https://github.com/sumup/sumup-php/commit/14fea98072a2a48af30f0dd99af6a0edecadb94f))
* **deps:** bump github.com/pb33f/libopenapi from 0.36.1 to 0.36.3 in /codegen ([#55](https://github.com/sumup/sumup-php/issues/55)) ([647bd76](https://github.com/sumup/sumup-php/commit/647bd76cd9354412bbdba09cb2774f9eb2f0dd33))

## [0.1.3](https://github.com/sumup/sumup-php/compare/v0.1.2...v0.1.3) (2026-04-23)


### Features

* improve generated request DTO ergonomics ([#49](https://github.com/sumup/sumup-php/issues/49)) ([4e71c06](https://github.com/sumup/sumup-php/commit/4e71c0623099dab82598316802f54caee4f5d02a))


### Chores

* synced file(s) with sumup/apis ([#46](https://github.com/sumup/sumup-php/issues/46)) ([ff477cc](https://github.com/sumup/sumup-php/commit/ff477cc42e7cd09570d8a86cd3b384f46c6b6b58))


### Build System & Dependencies

* **deps:** bump github.com/pb33f/libopenapi from 0.36.0 to 0.36.1 in /codegen ([#48](https://github.com/sumup/sumup-php/issues/48)) ([d76009f](https://github.com/sumup/sumup-php/commit/d76009fb848126018150408d2b988001e83cd500))
* **deps:** bump googleapis/release-please-action from 4.4.0 to 4.4.1 ([#47](https://github.com/sumup/sumup-php/issues/47)) ([ae9f15a](https://github.com/sumup/sumup-php/commit/ae9f15a2658d83a6b966e2c7fbd675c8eef69cc5))

## [0.1.2](https://github.com/sumup/sumup-php/compare/v0.1.1...v0.1.2) (2026-04-14)


### Features

* **sdk:** resource docs ([#44](https://github.com/sumup/sumup-php/issues/44)) ([c7cdac3](https://github.com/sumup/sumup-php/commit/c7cdac334982b223d36fb696504d9ed6d61a3678))


### Bug Fixes

* handle object without schemas and additionalProperties ([#45](https://github.com/sumup/sumup-php/issues/45)) ([34e6b20](https://github.com/sumup/sumup-php/commit/34e6b2080a2c2f344d38b649ecc9db199e85cfcf))


### Chores

* synced file(s) with sumup/apis ([#40](https://github.com/sumup/sumup-php/issues/40)) ([79f978c](https://github.com/sumup/sumup-php/commit/79f978cdf1d938d9d5a88c9d625a5c6364cc991a))


### Build System & Dependencies

* **deps:** bump actions/create-github-app-token from 3.0.0 to 3.1.1 ([#41](https://github.com/sumup/sumup-php/issues/41)) ([8883e4d](https://github.com/sumup/sumup-php/commit/8883e4d0a5267aa7464dd55a559e13e427cc8fa3))
* **deps:** bump github.com/pb33f/libopenapi from 0.35.0 to 0.36.0 in /codegen ([#42](https://github.com/sumup/sumup-php/issues/42)) ([45b8b1c](https://github.com/sumup/sumup-php/commit/45b8b1c375ab03c0721d4dd7aec3cc51c1185fb7))

## [0.1.1](https://github.com/sumup/sumup-php/compare/v0.1.0...v0.1.1) (2026-04-08)


### Features

* sync with latest openapi specs ([9509daf](https://github.com/sumup/sumup-php/commit/9509daf539d9f7af4a1422477b5b680726d7bc14))


### Bug Fixes

* **codegen:** prefer problem+json for response schemas ([#21](https://github.com/sumup/sumup-php/issues/21)) ([8d22914](https://github.com/sumup/sumup-php/commit/8d229143d9c936fdca391fa0408b97a1ce039e4d))
* **http:** set default problem+json accept header ([#20](https://github.com/sumup/sumup-php/issues/20)) ([9676d4a](https://github.com/sumup/sumup-php/commit/9676d4a404abe14b9b1d85a5628c7afe461f47b2))
* typing ([#39](https://github.com/sumup/sumup-php/issues/39)) ([42d6861](https://github.com/sumup/sumup-php/commit/42d6861fda96400418fd397469068fe8c002747f))


### Chores

* align release automation config ([#23](https://github.com/sumup/sumup-php/issues/23)) ([010fb65](https://github.com/sumup/sumup-php/commit/010fb65dbea0d7109fdb3fa6623062e2151aa8d3))
* synced file(s) with sumup/apis ([#16](https://github.com/sumup/sumup-php/issues/16)) ([5564126](https://github.com/sumup/sumup-php/commit/55641263d74682c9f643acba15e8c3521dc724a8))
* synced file(s) with sumup/apis ([#19](https://github.com/sumup/sumup-php/issues/19)) ([1673a64](https://github.com/sumup/sumup-php/commit/1673a64d058276bff28b6b99b4c9c6230005d453))
* synced file(s) with sumup/apis ([#22](https://github.com/sumup/sumup-php/issues/22)) ([cd99f92](https://github.com/sumup/sumup-php/commit/cd99f928491ed50437ae73af4bceab7e4bfce155))
* synced file(s) with sumup/apis ([#29](https://github.com/sumup/sumup-php/issues/29)) ([89a0f9a](https://github.com/sumup/sumup-php/commit/89a0f9a523d2bdf7d9f65155621127fc58cc1ddf))
* synced file(s) with sumup/apis ([#30](https://github.com/sumup/sumup-php/issues/30)) ([f0c2148](https://github.com/sumup/sumup-php/commit/f0c21484f5bbd90255a9667f9bdff6b451b6f7b4))
* synced file(s) with sumup/apis ([#31](https://github.com/sumup/sumup-php/issues/31)) ([74b0855](https://github.com/sumup/sumup-php/commit/74b08553140ef7d7625d7e540b96ff8613589333))
* synced file(s) with sumup/apis ([#32](https://github.com/sumup/sumup-php/issues/32)) ([35fd83d](https://github.com/sumup/sumup-php/commit/35fd83dd9e9e9595de3fb582b4883b0f319b9e1e))
* synced file(s) with sumup/apis ([#38](https://github.com/sumup/sumup-php/issues/38)) ([db9b275](https://github.com/sumup/sumup-php/commit/db9b2755e9db3a3944ffcd6ff194d5d49959dac9))


### Build System & Dependencies

* **deps:** bump actions/create-github-app-token from 2.2.1 to 3.0.0 ([#24](https://github.com/sumup/sumup-php/issues/24)) ([f8a48e2](https://github.com/sumup/sumup-php/commit/f8a48e2badcb420bf1d48706f5c2b7e9fb5b726b))
* **deps:** bump actions/setup-go from 6.2.0 to 6.3.0 ([#17](https://github.com/sumup/sumup-php/issues/17)) ([7c4e1b6](https://github.com/sumup/sumup-php/commit/7c4e1b6374f638ce74de4f126f33a7bf124b338e))
* **deps:** bump actions/setup-go from 6.3.0 to 6.4.0 ([#34](https://github.com/sumup/sumup-php/issues/34)) ([cdec404](https://github.com/sumup/sumup-php/commit/cdec404fa3b60a42bb16361cbe350d9960fb2760))
* **deps:** bump github.com/buger/jsonparser from 1.1.1 to 1.1.2 in /codegen in the go_modules group across 1 directory ([#26](https://github.com/sumup/sumup-php/issues/26)) ([2f54ea8](https://github.com/sumup/sumup-php/commit/2f54ea82186d20b2f8b35bc0489858fb6b99ea82))
* **deps:** bump github.com/pb33f/libopenapi from 0.33.11 to 0.34.0 in /codegen ([#18](https://github.com/sumup/sumup-php/issues/18)) ([0d3b9f9](https://github.com/sumup/sumup-php/commit/0d3b9f98b49c4b007a8949a139a0b163efbf0617))
* **deps:** bump github.com/pb33f/libopenapi from 0.34.0 to 0.34.3 in /codegen ([#28](https://github.com/sumup/sumup-php/issues/28)) ([b0f48a8](https://github.com/sumup/sumup-php/commit/b0f48a894f16aecafa5bbed2dedb79cc5df69059))
* **deps:** bump github.com/pb33f/libopenapi from 0.34.3 to 0.35.0 in /codegen ([#36](https://github.com/sumup/sumup-php/issues/36)) ([4c5eb74](https://github.com/sumup/sumup-php/commit/4c5eb74df2bf4a9d1d9c9433a7be83fe784ccb9a))
* **deps:** bump reviewdog/action-actionlint from 1.71.0 to 1.72.0 ([#35](https://github.com/sumup/sumup-php/issues/35)) ([0dbf237](https://github.com/sumup/sumup-php/commit/0dbf237e6b851853cda075a3df7edcb27e5b7132))
* **deps:** bump shivammathur/setup-php from 2.36.0 to 2.37.0 ([#27](https://github.com/sumup/sumup-php/issues/27)) ([cf700ec](https://github.com/sumup/sumup-php/commit/cf700ec2922ffacc098b7e809c1291c2d185487e))

## [0.1.0](https://github.com/sumup/sumup-php/compare/v0.0.1...v0.1.0) (2026-02-23)


### ⚠ BREAKING CHANGES

* **api:** property access like $sumup->checkouts is no longer supported; use $sumup->checkouts() instead.

### Features

* **api:** remove magic service properties from SumUp ([18c35c4](https://github.com/sumup/sumup-php/commit/18c35c41ea646ef705aea3a07fd14340714cfc37))
* **api:** type core client interfaces and service access ([5f15c0a](https://github.com/sumup/sumup-php/commit/5f15c0a7b001f840e998fd1c1076694c8f7709cb))
* **cd:** auto-generate SDK ([442dfeb](https://github.com/sumup/sumup-php/commit/442dfeb20a9c81e155b62a5aa8438139d740db69))
* **codegen:** consolidate component models into Types namespace ([08a0b17](https://github.com/sumup/sumup-php/commit/08a0b17cdf0a9ee22a0ba274bf400887f6f13bc0))
* **codegen:** emit typed service signatures ([6c7356f](https://github.com/sumup/sumup-php/commit/6c7356fd82cc37e6fcc0e37959af7c16c0658dc2))
* **errors:** decode OpenAPI error bodies and throw typed API exceptions ([df49313](https://github.com/sumup/sumup-php/commit/df4931374e7292b481b3ecd4ba66e6f5cb32bbc9))
* **http:** expose response headers and raw body for diagnostics ([f1d668d](https://github.com/sumup/sumup-php/commit/f1d668dbeda32976d7b20e4e9844223c9f55f7a7))
* improve service request ergonomics ([e1a1624](https://github.com/sumup/sumup-php/commit/e1a16245011977196bcde792f28b5b625c7efdbc))
* **php-sdk:** add error envelope and raise static analysis to level 6 ([17fd86b](https://github.com/sumup/sumup-php/commit/17fd86b3340c2ab3ca6368eee9d89d0c2dd306fd))
* **php-sdk:** replace magic request option arrays with typed RequestOptions ([3aa9a89](https://github.com/sumup/sumup-php/commit/3aa9a89ff7f9b4ff9e036671cd70557d498658b3))
* provide guzzle client out of the box ([f8f70df](https://github.com/sumup/sumup-php/commit/f8f70dfcb249d818c1a7f0536bd5b78f538d6eef))
* type schema-less request bodies in codegen ([1faa9ef](https://github.com/sumup/sumup-php/commit/1faa9ef99aa23543e04667b65a7d79737875b1e5))


### Bug Fixes

* **cd:** generate workflow write permission ([6697c8e](https://github.com/sumup/sumup-php/commit/6697c8ed58c15ab5d9454510c685e0d0f130bbe1))
* **codegen:** lint issues ([d4da561](https://github.com/sumup/sumup-php/commit/d4da5616c1621842763b0994a705ce5a7ef327b6))
* **docs:** README badges ([35570c5](https://github.com/sumup/sumup-php/commit/35570c59bb1fda27d23e4eec56e0991c3be4b899))
* **docs:** README examples, checkout example ([06159ec](https://github.com/sumup/sumup-php/commit/06159ec8fb6eb1dc4561251b8ccd2afcfe878d14))
* **hydrator:** support backed enum casting during hydration ([a15ee8d](https://github.com/sumup/sumup-php/commit/a15ee8d58905bb7752411a515ebe690cccfa888e))
* resolve phpstan type/docblock violations ([6b471ec](https://github.com/sumup/sumup-php/commit/6b471eca48e7afa3fc489053be349ea6f7f6de13))


### Miscellaneous Chores

* **cd:** adjust generate workflow ([12b6107](https://github.com/sumup/sumup-php/commit/12b6107c6280f704dda56702f141c150b3747a84))
* harden phpstan checks and CI gating ([6355192](https://github.com/sumup/sumup-php/commit/6355192541002cd20727fce49292d1e70b75a719))
* raise phpstan strictness to level 5 ([14fb68d](https://github.com/sumup/sumup-php/commit/14fb68de426333fb59e775dd0d0b73fd37afa74c))
* regenerate code ([e41bde2](https://github.com/sumup/sumup-php/commit/e41bde2d657155d95ce14fe520c2173175e8e338))
* synced file(s) with sumup/apis ([#5](https://github.com/sumup/sumup-php/issues/5)) ([ca75c20](https://github.com/sumup/sumup-php/commit/ca75c207ef4874145278fa55ccec42973cb7433f))
* synced file(s) with sumup/apis ([#8](https://github.com/sumup/sumup-php/issues/8)) ([3891105](https://github.com/sumup/sumup-php/commit/38911057e1ec3de920134894bc7c947d483c8a50))

## 0.0.1 (2026-01-31)


### Features

* authorization simplification ([282d2f5](https://github.com/sumup/sumup-php/commit/282d2f55a767ae8a52ac2aab6f8173701730363a))
* cleanup, deterministic output ([eebd1a8](https://github.com/sumup/sumup-php/commit/eebd1a85627b56d686935ebc06bdc5f9c205243f))
* custom http clients support ([cd00e04](https://github.com/sumup/sumup-php/commit/cd00e04ce6c53f26c417fb68b74fbccdf2e276f1))
* improve query params handling ([0eff5a6](https://github.com/sumup/sumup-php/commit/0eff5a67421d6912a6393b930d963b9a273ae81d))
* improve response parsin ([a2432fe](https://github.com/sumup/sumup-php/commit/a2432fe01190f83e647d4f88d792154c2c07870d))
* improve sdk typing ([1eb79bf](https://github.com/sumup/sumup-php/commit/1eb79bf4cfbfbb8bef93d6b56225024178b3bddb))
* init ([0a390f4](https://github.com/sumup/sumup-php/commit/0a390f43cb81cff0846bb938e94aad64a69a7840))
* oauth2 example ([3e1ff06](https://github.com/sumup/sumup-php/commit/3e1ff065dedd11f10b9f593ae64af1661b14c527))
* per-request options ([c0b8fe3](https://github.com/sumup/sumup-php/commit/c0b8fe3f8539386041ba5bdef5748cefe95eb7e2))
* phpdoc ([81964f1](https://github.com/sumup/sumup-php/commit/81964f174d230de11306e066c547a7816b3eaefd))
* release 0.0.1 ([386e628](https://github.com/sumup/sumup-php/commit/386e628846c3de0be75d9a5deba1666a3cab2b56))
* report runtime info ([#28](https://github.com/sumup/sumup-php/issues/28)) ([941c43e](https://github.com/sumup/sumup-php/commit/941c43e6e6f23c858cdfc6404285041a649d1f85))
* restructure ([3f98d9c](https://github.com/sumup/sumup-php/commit/3f98d9c6eae49e3c92c9259ebf042c2a8a55e9e3))
* support enums ([e443679](https://github.com/sumup/sumup-php/commit/e4436798aaa03b367828d8a286ff976245399859))


### Bug Fixes

* better schema generation ([cf0375b](https://github.com/sumup/sumup-php/commit/cf0375b35441bc0585a07a0da1e4c64c64885cfd))
* **codegen:** run make fmt ([8fac107](https://github.com/sumup/sumup-php/commit/8fac107775d50aaf939a1b86570f8db674391240))
* **docs:** README refs ([bbdd374](https://github.com/sumup/sumup-php/commit/bbdd374835f75137887def92ff329bd80722faf4))
* github actions ([135efbb](https://github.com/sumup/sumup-php/commit/135efbb94fb0187db48d304993db12cfef139729))
* response bodies for non-ref schemas ([056a961](https://github.com/sumup/sumup-php/commit/056a961f066431afd2b5cff1136ca747bd417d96))


### Miscellaneous Chores

* adjust release please config ([f57869c](https://github.com/sumup/sumup-php/commit/f57869c27329712945e79a6c6f1ee53b37ef6a73))
* bump deps for codegen ([dca9b76](https://github.com/sumup/sumup-php/commit/dca9b760ed12cd68705fdf0b5f5d147efd419337))
* cleanup and fix codegen ([614fe34](https://github.com/sumup/sumup-php/commit/614fe34b9d56fb2475f2d4fb972242d25035cc2f))
* cleanup auth ([47f621e](https://github.com/sumup/sumup-php/commit/47f621e9165e276cb7a6eaa8d364640face5e62f))
* cleanup password grant ([c164b70](https://github.com/sumup/sumup-php/commit/c164b70bf28a8264f116c18c80561a0ae71eb9d2))
* **codegen:** bump all deps ([366b14b](https://github.com/sumup/sumup-php/commit/366b14bca5b98ea36e753f84a4c75b1a8d12072a))
* docs updates ([04b563d](https://github.com/sumup/sumup-php/commit/04b563da4e5c3654209b713dfb42e8c34d614794))
* further refactoring ([244c192](https://github.com/sumup/sumup-php/commit/244c19248443d3257db5a2e1a92d316735266757))
* further simplification ([23819ed](https://github.com/sumup/sumup-php/commit/23819ed3801e31df929c306825b9b3f9ae0c9256))
* major refactor and cleanup ([aa48bd2](https://github.com/sumup/sumup-php/commit/aa48bd20f9aca71376dd9f9b73b81c537445f71b))
* more cleanup and fixups ([358e86e](https://github.com/sumup/sumup-php/commit/358e86e650b02ff641c6e5f1e9a6978e073e44ad))
* prepare for public ([0b2b926](https://github.com/sumup/sumup-php/commit/0b2b92657aeae221ecd6ad34e5185179c7097458))
* remove form encoding header ([0db9a49](https://github.com/sumup/sumup-php/commit/0db9a49e0c5b11a5430a1c74a1228a3dd32be41f))
* remove openapi.yaml ([8604895](https://github.com/sumup/sumup-php/commit/8604895945553a9d5b44e0f9d4d8ff3accbcde48))
* **sdk:** cleanup exceptions ([c5467f5](https://github.com/sumup/sumup-php/commit/c5467f5baf9a0138aef135b2b3de240281f5cc6d))
* simplify configuration ([189e937](https://github.com/sumup/sumup-php/commit/189e937bff4fa4adfaa75ec52ec481397889e339))
* simplify setup of services ([342341a](https://github.com/sumup/sumup-php/commit/342341ad60654bf09335f5f891bd25e2380cac6c))
