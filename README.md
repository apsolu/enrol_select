# enrol_select

[![Build Status](https://travis-ci.org/apsolu/enrol_select.svg?branch=master)](https://travis-ci.org/apsolu/enrol_select)
[![Coverage Status](https://coveralls.io/repos/github/apsolu/enrol_select/badge.svg?branch=master)](https://coveralls.io/github/apsolu/enrol_select?branch=master)
[![Moodle Status](https://img.shields.io/badge/moodle-3.7-blue)](https://moodle.org)

## Description

Une méthode d'inscription permettant de proposer une liste de cours aux étudiants avec des quotas.


## Installation

```bash
cd /your/moodle/path
git clone https://github.com/apsolu/enrol_select enrol/select
php admin/cli/upgrade.php
```


## Développement

Structure base de données:
- customint1 : nombre de places sur liste principale
- customint2 : nombre de places sur liste complémentaire
- customint3 : témoin d'activation des quotas
- customint4 : date de début des réinscriptions
- customint5 : date de fin des réinscriptions
- customint6 : id de la méthode vers laquelle les utilisateurs seront réinscrits
- customint7 : date de début du cours
- customint8 : date de fin du cours


## Reporting security issues

We take security seriously. If you discover a security issue, please bring it
to their attention right away!

Please **DO NOT** file a public issue, instead send your report privately to
[foss-security@univ-rennes2.fr](mailto:foss-security@univ-rennes2.fr).

Security reports are greatly appreciated and we will publicly thank you for it.
