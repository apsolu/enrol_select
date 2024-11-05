# enrol_select

[![Build Status](https://github.com/apsolu/enrol_select/workflows/Moodle%20Plugin%20CI/badge.svg?branch=master)](https://github.com/apsolu/enrol_select/actions)
[![Coverage Status](https://coveralls.io/repos/github/apsolu/enrol_select/badge.svg?branch=master)](https://coveralls.io/github/apsolu/enrol_select?branch=master)
[![Moodle Status](https://img.shields.io/badge/moodle-4.4-blue)](https://moodle.org)

## Description

Ce module réunit sur une page l'ensemble de l'offre de formation proposée aux utilisateurs.
- permet de définir une période d'inscription
- permet de définir une période de réinscription (entre 2 semestres)
- permet la gestion de listes principales et de listes complémentaires
- permet de définir des quotas
- permet de réserver un cours à une population


## Installation

```bash
cd /your/moodle/path
git clone https://github.com/apsolu/enrol_select enrol/select
php admin/cli/upgrade.php
```


## Informations techniques

Le plugin `enrol_select` utilise les champs personnaliés de la table Moodle `enrol` de la façon suivante :
- customint1 : nombre de places sur liste principale
- customint2 : nombre de places sur liste complémentaire
- customint3 : témoin d'activation des quotas
- customint4 : date de début des réinscriptions
- customint5 : date de fin des réinscriptions
- customint6 : id de la méthode vers laquelle les utilisateurs seront réinscrits
- customint7 : date de début du cours
- customint8 : date de fin du cours
- customchar1 : calendrier utilisé  0/null ou un id de la table `apsolu_calendars`

## Reporting security issues

We take security seriously. If you discover a security issue, please bring it
to their attention right away!

Please **DO NOT** file a public issue, instead send your report privately to
[foss-security@univ-rennes2.fr](mailto:foss-security@univ-rennes2.fr).

Security reports are greatly appreciated and we will publicly thank you for it.
