#!/bin/sh
# Credential helper éphémère pour git (GIT_ASKPASS) — story 008.
#
# git invoque ce script pour chaque prompt (« Username », « Password ») lors d'un
# clone/pull HTTPS. On renvoie le token pour les deux : GitHub comme GitLab acceptent le
# PAT en mot de passe, un username non vide suffit. Le token n'apparaît donc jamais en
# argv (visible dans `ps`) ni dans le `.git/config` du clone — il transite uniquement par
# la variable d'environnement du process parent.
printf '%s' "$GIT_ASKPASS_TOKEN"
