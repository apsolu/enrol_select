<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'enrol_select', language 'en'.
 *
 * @package    enrol_select
 * @copyright  2016 Université Rennes 2 <dsi-contact@univ-rennes2.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['date_diverging_from_calendar_date'] = 'Date divergente par rapport à la date du calendrier';
$string['enrollee_accepted_list'] = 'Candidat Inscription';
$string['enrollee_deleted_list'] = 'Candidat Refusé';
$string['enrollee_main_list'] = 'Candidat Principale';
$string['enrollee_wait_list'] = 'Candidat Attente';
$string['enrollee_wish_list'] = 'Candidat Voeux';
$string['enrollees'] = 'Inscrits';
$string['filters_for_managers'] = 'Filtres pour gestionnaires';
$string['list'] = 'Liste';
$string['lists'] = 'Listes';
$string['maximum_enrolments'] = 'Nombre d\'inscriptions maximum';
$string['maximum_enrolments_must_be_greater_than_or_equal_to_minimum_enrolments'] = 'Le nombre d\'inscriptions maximum doit être égal ou supérieur au nombre d\'inscriptions minimum.';
$string['maximum_wishes'] = 'Nombre de voeux maximum';
$string['maximum_wishes_must_be_greater_than_or_equal_to_maximum_enrolments'] = 'Le nombre de voeux maximum doit être égal ou supérieur au nombre d\'inscriptions maximum.';
$string['minimum_enrolments'] = 'Nombre d\'inscriptions minimum';
$string['no_available_cohorts'] = 'Aucune cohorte disponible';
$string['no_available_enrol_methods_desc'] = 'Pour utiliser cette fonctionnalité, une deuxième méthode d\'inscription par voeux est nécessaire dans ce cours.';
$string['no_available_prices'] = 'Aucun tarif défini';
$string['no_available_roles'] = 'Aucun rôle disponible';
$string['no_quotas'] = 'Aucun quota';
$string['no_seat_restrictions'] = 'Aucune restriction de place';
$string['no_places_available'] = 'Aucune place disponible';
$string['number_of_accepted_enrolments'] = 'Nombre d\'inscriptions acceptées';
$string['number_of_deleted_enrolments'] = 'Nombre de désinscriptions';
$string['number_of_enrolments_on_main_list'] = 'Nombre d\'inscriptions sur liste principale';
$string['number_of_enrolments_on_waiting_list'] = 'Nombre d\'inscriptions sur liste d\'attente';
$string['number_of_wishes'] = 'Nombre de voeux';
$string['overview'] = 'Vue d\'ensemble';
$string['pluginname'] = 'Inscription par voeux';
$string['pluginname_desc'] = 'Le plugin d\'inscription par voeux permet aux utilisateurs de choisir les cours qu\'ils veulent suivre. Les cours peuvent être protégés par différents critères (période d\'inscription, taille de la liste principale, cohortes, etc).';
$string['reenrolment_disabled'] = 'Réinscription désactivée';
$string['there_are_still_places_on_the_wait_list'] = 'Il reste des places sur liste complémentaire';
$string['x_place_remaining_on_the_main_list'] = '{$a} place restante sur liste principale';
$string['x_place_remaining_on_the_wait_list'] = '{$a} place restante sur liste complémentaire';
$string['x_places_remaining_on_the_main_list'] = '{$a} places restantes sur liste principale';
$string['x_places_remaining_on_the_wait_list'] = '{$a} places restantes sur liste complémentaire';
$string['you_are_on_X_list'] = 'Vous êtes sur {$a}.';
$string['your_wish_has_been_registered'] = 'Votre vœu a été enregistré.';

// Permissions.
$string['select:config'] = 'Configurer les instances d\''.strtolower($string['pluginname']);
$string['select:enrol'] = 'Inscrire des utilisateurs';
$string['select:manage'] = 'Gérer les utilisateurs inscrits';
$string['select:unenrol'] = 'Désinscrire du cours les utilisateurs';
$string['select:unenrolself'] = 'Se désinscrire du cours';

$string['enrolname'] = 'Nom de l\'instance d\'inscription';

// Edit form.
$string['enableinstance'] = 'Activer cette méthode d\'inscription';
$string['enroldate'] = 'Date des inscriptions';
$string['enrolstartdate'] = 'Date d\'ouverture des inscriptions';
$string['enrolenddate'] = 'Date de fermeture des inscriptions';
$string['coursedate'] = 'Date des cours';
$string['coursestartdate'] = 'Date de début du cours';
$string['courseenddate'] = 'Date de fin du cours';
$string['reenroldate'] = 'Date des réinscriptions';
$string['reenrolstartdate'] = 'Date d\'ouverture des réinscriptions';
$string['reenrolenddate'] = 'Date de fermeture des réinscriptions';
$string['reenrolinstance'] = 'Instance de réinscription';
$string['reenrolinstance_help'] = 'L\'instance de réiniscription devrait toujours être configurée au premier semestre. Dans le menu déroulant, il faut cibler une instance du second semestre.';
$string['quotas'] = 'Quotas';
$string['enablequotas'] = 'Activer les quotas';
$string['cohorts'] = 'Cohortes';
$string['selectcohorts'] = 'Sélectionner les populations (cohortes)';
$string['registertype'] = 'Type d\'inscription';

$string['enrolenddateerror'] = 'La date de fin des inscriptions ne peut être antérieure à celle du début';
$string['courseenddateerror'] = 'La date de fin du cours ne peut être antérieure à celle du début';
$string['reenrolenddateerror'] = 'La date de fin des réinscriptions ne peut être antérieure à celle du début';
$string['reenrolstartdatemissingerror'] = 'La date de début des réinscriptions doit être renseignée si la date de fin est présente';
$string['reenrolenddatemissingerror'] = 'La date de fin des réinscriptions doit être renseignée si la date de début est présente';

$string['cohortonly'] = 'Seulement les membres de la cohorte';
$string['cohortnonmemberinfo'] = 'Only members of cohort \'{$a}\' can enrol.';

$string['max_places'] = 'Nombre de places sur liste principale';
$string['free_places'] = 'Places disponibles';

$string['overviewtitle'] = 'Inscription aux activités';
$string['overviewtitlemanager'] = 'Inscription aux activités (vue gestionnaire)';
$string['back_to_dashboard'] = 'Revenir à mon tableau de bord';

// Bloc filtres.
$string['filters'] = 'Filtres';
$string['reset_filters'] = 'Réinitialiser les filtres';

$string['activities_list'] = 'Liste des créneaux par activité';
$string['activities'] = 'Activités sportives';
$string['no_activities'] = 'Aucune activité ouverte aux inscriptions.';

$string['complements_list'] = 'Liste des activités complémentaires';
$string['complements'] = 'Activités complémentaires';
$string['no_complements'] = 'Aucune activité complémentaire ouverte aux inscriptions.';

$string['max_waiting_places'] = 'Nombre de places sur liste complémentaire';
$string['role'] = 'Rôle attribué par défaut';

$string['status'] = 'Activer cette méthode d\'inscription';
$string['general'] = 'Général';

$string['types'] = 'Cours évalué';
$string['wishes'] = 'Voeux';
$string['roles'] = 'Rôles';
$string['prices'] = 'Tarification';
$string['colleges'] = 'Population';
$string['renewals'] = 'Réinscriptions en masse';

$string['accepted_list'] = 'Liste des étudiants acceptés';
$string['accepted_list_abbr'] = 'Accepté';
$string['accepted_list_short'] = 'Accepté';
$string['accepted_description'] = 'Liste des étudiants acceptés en cours. Ils ont accès aux forums et aux documents du cours. Une liste de toutes les sessions à venir est également indiquée sur leur page d\'accueil.';
$string['main_list'] = 'Liste principale';
$string['main_list_abbr'] = 'LP';
$string['main_list_short'] = 'Principale';
$string['main_description'] = 'Liste des étudiants sur liste principale. Ils n\'ont accès ni aux forums, ni aux documents du cours. Seule la première session du cours est indiquée sur leur page d\'accueil.';
$string['main_list_registered'] = 'Inscrit sur liste principale';
$string['wait_list'] = 'Liste complémentaire';
$string['wait_list_abbr'] = 'LC';
$string['wait_list_short'] = 'Complément';
$string['wait_description'] = 'Liste des étudiants sur liste complémentaire. Ils n\'ont accès ni aux forums, ni aux documents du cours. Seule la première session du cours est indiquée sur leur page d\'accueil.';
$string['wait_list_registered'] = 'Inscrit sur liste complémentaire';
$string['deleted_list'] = 'Liste des étudiants désinscrits';
$string['deleted_list_abbr'] = 'Désins.';
$string['deleted_list_short'] = 'Désinscrit';
$string['deleted_description'] = 'Liste des étudiants désinscrits. Ils n\'ont accès ni aux forums, ni aux documents du cours. Ce cours n\'est pas référencé sur leur page d\'accueil.';
$string['error:enrol'] = 'Impossible de vous inscrire à ce cours';

$string['enrolment'] = 'Inscription';
$string['enrolmentsaved'] = 'Vœu enregistré';
$string['unenrolmentsaved'] = 'Désinscription effectuée';
$string['enrol'] = 'S\'inscrire';
$string['unenrol'] = 'Se désinscrire';
$string['edit_enrol'] = 'Modifier son type d\'inscription';
$string['change_course'] = 'Déplacer dans un autre cours';

$string['unenroled'] = 'Désinscrit';

$string['canntenrol'] = 'canntenrol';

$string['rolename_and_price'] = '{$a->rolename} - {$a->price} {$a->currency}';
$string['rolename_and_price'] = '{$a->rolename}';
$string['rolename_and_price_free'] = '{$a} - gratuit';

$string['event_user_moved'] = 'Utilisateur déplacé';
$string['event_user_notified'] = 'Utilisateur notifié';
$string['full_registration'] = 'Inscription complète';

$string['maxwishes'] = 'Nombre de voeux maximum';
$string['maxwishes_help'] = 'Nombre de voeux maximum par défaut attribué à un utilisateur n\'étant pas clairement identifié dans le système d\'information ; n\'appartenant à aucune population définie.';

$string['manage_notify'] = 'Envoyer une notification aux étudiants sélectionnés';
$string['manage_notification_0'] = 'Vous avez été retiré de la liste des étudiants acceptés.';
$string['manage_notification_2'] = 'Vous avez été retiré de la liste principale.';
$string['manage_notification_3'] = 'Vous avez été retiré de la liste complémentaire.';
$string['manage_notification_4'] = 'Vous avez été retiré de la liste des étudiants refusés.';

$string['move_to'] = 'Déplacer';
$string['move_to_accepted'] = 'Déplacer dans la liste des étudiants acceptés';
$string['move_to_main'] = 'Déplacer dans la liste principale';
$string['move_to_next_accepted'] = 'Réinscrire dans la liste des étudiants acceptés';
$string['move_to_next_deleted'] = 'Réinscrire dans la liste des étudiants désinscrits';
$string['move_to_next_main'] = 'Réinscrire dans la liste principale';
$string['move_to_next_wait'] = 'Réinscrire dans la liste complémentaire';
$string['move_to_wait'] = 'Déplacer dans la liste complémentaire';
$string['move_to_deleted'] = 'Déplacer dans la liste des étudiants désinscrits';

$string['notify'] = 'Notifier par email';
$string['editenroltype'] = 'Modifier le type d\'inscription';

$string['send_message'] = 'Envoyer un message';

$string['goto'] = 'Déplacer de la liste {$a->from} vers la liste {$a->to}';
$string['list_accepted'] = '"accepté"';
$string['list_main'] = 'principale';
$string['list_next_accepted'] = '"accepté" du prochain semestre';
$string['list_next_main'] = 'principale du prochain semestre';
$string['list_next_wait'] = 'complémentaire du prochain semestre';
$string['list_next_deleted'] = '"désinscrit" du prochain semestre';
$string['list_wait'] = 'complémentaire';
$string['list_deleted'] = '"désinscrit"';

$string['message_accepted_to_main'] = 'Bonjour,

Vous avez été déplacé de la liste des inscrits à la liste principale.

Cordialement,';
$string['message_accepted_to_wait'] = str_replace('principale', 'complémentaire', $string['message_accepted_to_main']);
$string['message_accepted_to_deleted'] = str_replace('principale', 'désinscrits', $string['message_accepted_to_main']);

$string['message_main_to_accepted'] = str_replace('Vous avez été déplacé de la liste des inscrits à la liste principale', 'Votre pré-inscription a été confirmée', $string['message_accepted_to_main']);
$string['message_main_to_wait'] = str_replace('des inscrits', 'principale', $string['message_accepted_to_wait']);
$string['message_main_to_deleted'] = str_replace('des inscrits', 'principale', $string['message_accepted_to_deleted']);

$string['message_accepted_to_next_main'] = 'Bonjour,

Vous avez été déplacé de la liste des inscrits du semestre précédent à la liste principale du prochain semestre.

Cordialement,';
$string['message_accepted_to_next_accepted'] = str_replace('principale', 'des inscrits', $string['message_accepted_to_next_main']);
$string['message_main_to_next_main'] = str_replace('des inscrits', 'principale', $string['message_accepted_to_next_main']);
$string['message_accepted_to_next_wait'] = str_replace('principale', 'complémentaire', $string['message_accepted_to_next_main']);
$string['message_accepted_to_next_deleted'] = str_replace('principale', 'désinscrits', $string['message_accepted_to_next_main']);

$string['message_main_to_next_accepted'] = str_replace('Vous avez été déplacé de la liste des inscrits du semestre précédent à la liste principale du prochain semestre', 'Votre ré-inscription a été confirmée', $string['message_accepted_to_next_main']);
$string['message_main_to_next_wait'] = str_replace('des inscrits', 'principale', $string['message_accepted_to_next_wait']);
$string['message_main_to_next_deleted'] = str_replace('des inscrits', 'principale', $string['message_accepted_to_next_deleted']);

$string['message_wait_to_next_accepted'] = str_replace('Vous avez été déplacé de la liste des inscrits du semestre précédent à la liste principale du prochain semestre', 'Votre ré-inscription a été confirmée', $string['message_accepted_to_next_main']);
$string['message_wait_to_next_main'] = str_replace('des inscrits', 'principale', $string['message_accepted_to_next_main']);
$string['message_wait_to_next_deleted'] = str_replace('des inscrits', 'complémentaire', $string['message_accepted_to_next_deleted']);

$string['message_deleted_to_next_accepted'] = str_replace('de la liste des inscrits du semestre précédent à la liste principale', 'dans la liste des inscrits', $string['message_accepted_to_next_main']);
$string['message_deleted_to_next_main'] = str_replace('de la liste des inscrits à la liste principale', 'dans la liste principale', $string['message_accepted_to_next_main']);
$string['message_deleted_to_next_wait'] = str_replace('de la liste des inscrits à la liste principale', 'dans la liste complémentaire', $string['message_accepted_to_next_main']);

$string['message_wait_to_accepted'] = str_replace('Vous avez été déplacé de la liste des inscrits à la liste principale', 'Votre pré-inscription a été confirmée', $string['message_accepted_to_main']);
$string['message_wait_to_main'] = str_replace('des inscrits', 'principale', $string['message_accepted_to_main']);
$string['message_wait_to_deleted'] = str_replace('des inscrits', 'complémentaire', $string['message_accepted_to_deleted']);

$string['message_deleted_to_accepted'] = str_replace('de la liste des inscrits à la liste principale', 'dans la liste des inscrits', $string['message_accepted_to_main']);
$string['message_deleted_to_main'] = str_replace('de la liste des inscrits à la liste principale', 'dans la liste principale', $string['message_accepted_to_main']);
$string['message_deleted_to_wait'] = str_replace('de la liste des inscrits à la liste principale', 'dans la liste complémentaire', $string['message_accepted_to_main']);

$string['message_promote'] = 'Bonjour,

Suite à un désistement, vous avez été placé sur liste principale.

Cordialement,';

$string['notifystudents'] = 'Notifier les étudiants';
$string['message'] = 'Message';
$string['enrolcoursesubject'] = '[{$a->fullname}] Situation de votre inscription';

$string['eula'] = 'Certificat d\'aptitude au sport';
$string['eula_help'] = 'Texte présenté à tous les étudiants avant toute inscription à une activité sportive et nécessitant une acceptation de leur part.';

// Manage.
$string['xls_export'] = 'Exporter au format Excel';
$string['lockedform'] = 'Semestre verrouillé';
$string['no_users'] = 'Aucun utilisateur dans cette liste';
$string['select'] = 'Sélectionner';
$string['lmd'] = 'LMD';
$string['all_registers'] = 'Toutes les inscriptions';
$string['register_date'] = 'Date d\'inscription';

// Variables pour l'export csv des listes étudiantes.
$string['age'] = 'Âge';
$string['birthday'] = 'Date de naissance';
$string['sex'] = 'Sexe';
$string['register_type'] = 'Type d\'inscription';
$string['paid'] = 'Carte sport payée';

// Licence FFSU.
$string['federation_required'] = 'Adhésion à l\'association sportive (obligatoire)';
$string['federation_required_help'] = 'L\'adhésion à l\'association sportive permet de faire des compétitions en dehors des heures de cours. Elle coûte 15€ et vous fait adhérer automatiquement à la Fédération Française des Sports Universitaires (FFSU)';
$string['federation_optional'] = 'Adhésion à l\'association sportive (facultatif)';
$string['federation_optional_help'] = $string['federation_required_help'];

$string['html_role_notifications'] = '<div class="alert alert-info"><p>Seule la première inscription par type de voeux est payante. Les inscriptions suivantes sont gratuites.</p>'.
    '<p>Exemple: La 1ère inscription en libre (non évaluée) est égale à 40€. La 2nd inscription en libre est gratuite. Vous payerez au total 40€ (et non 80€).</p></div>';

$string['error_no_left_slot'] = 'Il n\'y a pas plus de place disponible pour ce cours.';
$string['error_reach_wishes_limit'] = 'Vous avez atteint le nombre maximum de voeux.';
$string['error_reach_wishes_role_limit'] = 'Vous avez atteint le nombre maximum de voeux de type {$a}.';
$string['error_cannot_enrol'] = 'Vous ne pouvez pas vous inscrire à ce cours.';
$string['error_no_role'] = 'Vous devez sélectionner au moins un rôle pour pouvoir inscrire un utilisateur.';

// Renew form.
$string['strftimedaydatetime'] = '%A %d %B %Y à %Hh%M';
$string['renewtitle'] = 'Réinscription aux activités';
$string['reenrolment'] = 'Réinscription';
$string['closedreenrolment'] = '<p class="alert alert-info">La période de réinscription est fermée.</p>';
$string['nextreenrolment'] = '<p class="alert alert-info">La prochaine période de réinscription est prévue à partir du <strong>{$a->from}</strong>.</p>';
$string['noreenrolment'] = '<div class="alert alert-warning"><p>Aucune de vos inscriptions actuelles ne propose de réinscription.</div>';
$string['nextenrolment'] = '<div class="alert alert-info"><p>Les préinscriptions du 2ème semestre auront lieu à partir du <strong>{$a->from}</strong>.</p></div>';
$string['reenrolmentexplanationcase'] = '<div class="alert alert-info">'.
    '<ol>'.
    '<li>vous souhaitez poursuivre sur le même créneau, il vous suffit de compléter et d\'enregistrer le tableau ci-dessous</li>'.
    '<li>vous souhaitez changer de créneau avec le même enseignant, <strong>contactez-le vite par mail <u>avant le {$a->limit}</u></strong></li>'.
    '<li>vous souhaitez vous inscrire sur un autre cours avec un nouvel enseignant, revenez vous préinscrire sur « votre espace SIUAPS » à partir du <strong>{$a->from}</strong></li>'.
    '</ol>'.
    '</div>';
$string['reenrolmentexplanationcasenoenrol'] = '<div class="alert alert-info">'.
    '<ol>'.
    '<li>vous souhaitez poursuivre sur le même créneau, il vous suffit de compléter et d\'enregistrer le tableau ci-dessous</li>'.
    '<li>vous souhaitez changer de créneau avec le même enseignant, <strong>contactez-le vite par mail <u>avant le {$a->limit}</u></strong></li>'.
    '</ol>'.
    '</div>';
$string['coursename'] = 'Nom du cours';
$string['teachercontact'] = 'Contact enseignant';
$string['renewenrolement'] = 'Renouveler mon inscription';
$string['savedreenrolment'] = 'Votre choix a été enregistré.<br />Vous pouvez revenir sur votre sélection à tout moment jusqu\'au {$a->date}.';
$string['reenrolmentnotificationsubject'] = 'Récapitulatif de vos réinscriptions au SIUAPS';
$string['reenrolmentnotification'] = 'Bonjour,'.PHP_EOL.PHP_EOL.
    'Vous avez choisi de :'.PHP_EOL.
    '{$a->choices}'.PHP_EOL.PHP_EOL.
    'En cas de demande de réinscription, il vous appartient maintenant de vous présenter (avec votre tenue) sur le lieu et à l\'heure du cours lors de la semaine de rentrée au SIUAPS - voir "mes rendez-vous à venir"'.PHP_EOL.PHP_EOL.
    'À bientôt,'.PHP_EOL.PHP_EOL.
    'L\'équipe du SIUAPS';
$string['reenrolmentcontinue'] = 'poursuivre le cours {$a->fullname}';
$string['reenrolmentstop'] = 'quitter le cours {$a->fullname}';

// Debug.
$string['debug_enrol_invalid_enrolment'] = 'Le cours #{$a->courseid} n\'est pas un créneau apsolu. La méthode d\'inscription #{$a->enrolid} a été ignorée.';
$string['debug_enrol_invalid_category'] = 'Le cours #{$a->courseid} n\'est pas rattaché à une activité sportive apsolu (catégorie #{$a->categoryid}.';
$string['debug_enrol_no_enrolments'] = 'Le cours #{$a->courseid} n\'offre aucune méthode d\'inscription par voeux valide pour l\'utilisateur #{$a->userid}.';
$string['debug_enrol_too_many_enrolments'] = 'Le cours #{$a->courseid} offre plus d\'une méthode d\'inscription par voeux valide pour l\'utilisateur #{$a->userid}.';
