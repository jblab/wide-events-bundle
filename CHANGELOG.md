# 1.0.0 (2026-09-16)


### Bug Fixes

* **bundle:** register HTTP lifecycle subscriber ([3cece3f](https://github.com/jblab/wide-events-bundle/commit/3cece3f75be17a7ab0624a5ebf021f94f5aafd70))
* **sampling:** use outcome duration for slow-event retention ([258ee11](https://github.com/jblab/wide-events-bundle/commit/258ee1120d05abf8485e0dda7365ba2f61b7e0ec))


### Features

* **architecture:** enforce component isolation boundaries ([9f1d93d](https://github.com/jblab/wide-events-bundle/commit/9f1d93d2a4522c012c7e7ed265a7373c58e8fc6b))
* **bundle:** add initial Symfony bundle configuration ([3fc328d](https://github.com/jblab/wide-events-bundle/commit/3fc328db7c0638da8be8aa2db255391af127650c))
* **bundle:** add Symfony dependency injection foundation ([dc51418](https://github.com/jblab/wide-events-bundle/commit/dc514183b1ddc74af508eab621f9d484af515248))
* **core:** add mutable event context ([cbcd7b1](https://github.com/jblab/wide-events-bundle/commit/cbcd7b1f3b04324c0b01524db96f1c293bac6a15))
* **core:** add WideEvent and WideEventContext ([d037ca2](https://github.com/jblab/wide-events-bundle/commit/d037ca2ac6affbbc71a53ea0d6285bda04bbec25))
* **emission:** add in-memory and wide event emitters ([ef82773](https://github.com/jblab/wide-events-bundle/commit/ef827733da2f7652558f436bb2d2e90af3b2f564))
* **http:** add Symfony HTTP lifecycle integration ([2d75140](https://github.com/jblab/wide-events-bundle/commit/2d7514083ec1b1f0766dac9ff8b26abc8d030768))
* **http:** defer exception telemetry and propagate request IDs ([665e679](https://github.com/jblab/wide-events-bundle/commit/665e6796838d067894bd52a5f45adf700e234808))
* **messenger:** add wide-event middleware and correlation stamp ([759364e](https://github.com/jblab/wide-events-bundle/commit/759364eecaebca27204cbd361f6b0a02b56aaf18))
* **monolog:** add structured wide-event emitter ([73ddfd3](https://github.com/jblab/wide-events-bundle/commit/73ddfd333acf6f754f6680db716f0f425c0ba1ed))
* **normalization:** add event limits and normalization ([cfb6362](https://github.com/jblab/wide-events-bundle/commit/cfb636253a1f5259406d276a330e2311256ae250))
* **normalization:** redact sensitive event data ([f9ba07d](https://github.com/jblab/wide-events-bundle/commit/f9ba07d780f0e4e45f865eb2bdd7a077bbdc8e80))
* **opentelemetry:** correlate events with active traces ([3d127e5](https://github.com/jblab/wide-events-bundle/commit/3d127e54ad802bb94ad1e380f4c1cbfa0b05eb4e))
* **sampling:** add tail-based event samplin ([fa81d89](https://github.com/jblab/wide-events-bundle/commit/fa81d89fc1fdb5ad2c57ea2ed57587ebe8531621))
