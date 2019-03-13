#!/usr/bin/env bash

source $(dirname $0)/common.sh

updateLocalBranches

mergeBranches "master" "release"
mergeBranches "release" "qualif"
mergeBranches "qualif" "develop"
