#!/usr/bin/env bash

set -e

function block {
    local titleLength=${#2}
    echo -en "\n\e[$1m\e[1;37m    "
    for x in $(seq 1 $titleLength); do echo -en " "; done;
    echo -en "\e[0m\n"

    echo -en "\e[$1m\e[1;37m  $2  \e[0m\n"
    echo -en "\e[$1m\e[1;37m    "
    for x in $(seq 1 $titleLength); do echo -en " "; done;
    echo -en "\e[0m\n\n"
}

function title {
    block 46 "$1"
}

function ask() {
    echo -en "\e[44m\e[1;37m $1 \e[0m "
}

function echoInfo {
    echo -en "\e[42m\e[1;37m $1 \e[0m "
}

function cancelScript {
    if [ "$1" = "" ]; then
        message="Script canceled, error occured."
    else
        message=$1
    fi
    echo -en "\n\n"
    block 41 "$message"
    exit 1
}

function updateLocalBranch {
    local branch=$1

    echo -en "\e[43m\e[1;37m $branch \e[0m\n"

    git checkout $branch
    git merge origin/$branch
}

function updateLocalBranches {
    title "Updating local branches from origin"
    git fetch origin
    updateLocalBranch "develop"
    updateLocalBranch "qualif"
    updateLocalBranch "release"
    updateLocalBranch "master"
}

function mergeBranches {
    local sourceBranch=$1
    local destinationBranch=$2
    local tagName=$3

    title "Merge $sourceBranch into $destinationBranch"

    local commitsCount=$(git log $destinationBranch..$sourceBranch --pretty=format:'%h' | wc -w)
    if [ $commitsCount == 0 ]; then
        echoInfo "No commits to merge: $destinationBranch already contains all commits from $sourceBranch."
        echo -e "\n"
    else
        echo -en "\e[43m\e[1;37m $commitsCount \e[0m commits to merge.\n"

        ask "Show commits? [y/N]"
        read showCommits
        if [ "$showCommits" == "y" ]; then
            git log $destinationBranch..$sourceBranch --abbrev-commit --reverse --pretty=format:'%Cred%h%Creset -%C(yellow)%d%Creset %s %Cgreen(%cr) %C(bold blue)<%an>%Creset'
        fi

        ask "Take commits from $sourceBranch, merge it into $destinationBranch then push? [Y/n]"
        read mergeCommits
        if [ "$mergeCommits" == "" ] || [ "$mergeCommits" == "y" ] || [ "$mergeCommits" == "Y" ]; then
            git checkout $destinationBranch
            git merge $sourceBranch
            git push origin $destinationBranch

            if [ "$tagName" != "" ]; then
                ask "Create tag $tagName on last commit of $destinationBranch? [Y/n]"
                read createTag
                if [ "$createTag" == "" ] || [ "$createTag" == "y" ]; then
                    git tag "$tagName"
                    git push --tags
                fi
            fi
        fi
    fi
}

function onExit {
    echo -e "\n"
    git checkout $workingBranch
}

workingBranch=$(git rev-parse --abbrev-ref HEAD)
trap onExit exit
