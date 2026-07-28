#!/bin/bash -ex
[[ $1 == "css" ]] && sass --style compressed --no-source-map src/scss/index.scss nunsontherun.css
bga nunsontherun