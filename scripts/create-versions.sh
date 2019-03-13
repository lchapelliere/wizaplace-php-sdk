#!/usr/bin/env bash

source $(dirname $0)/common.sh

updateLocalBranches

mergeBranches "release" "master" "1.48.0"
mergeBranches "qualif" "release" "1.49.0-rc.0"
mergeBranches "develop" "qualif"
