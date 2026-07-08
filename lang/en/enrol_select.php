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
 * @copyright  2016 Université Rennes 2
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.LangFilesOrdering.DuplicatedKey
// phpcs:disable moodle.Files.LangFilesOrdering.IncorrectOrder
// phpcs:disable moodle.Files.LangFilesOrdering.UnexpectedComment

$string['batch_settings'] = 'Paramétrage par lots';
$string['cachedef_colleges'] = 'Liste des populations';
$string['cachedef_courses'] = 'Liste des créneaux horaires';
$string['cachedef_enrolments'] = 'Liste des inscriptions';
$string['cachedef_enrols'] = 'Liste des méthodes d’inscription par voeux';
$string['cachedef_users'] = 'Liste des inscriptions et des populations d’appartenance des utilisateurs';
$string['check_enrolment_payment'] = 'Contrôle le paiement d’une inscription';
$string['cohort_X_is_already_used_with_role_Y_by_college_Z'] = 'La cohorte « {$a->cohort} » est déjà utilisée avec le rôle « {$a->role} » par la population « {$a->college} ».';
$string['college_unused_cohorts'] = '<details class="alert alert-info"><summary class="mb-3">Information</summary><p>Ces cohortes ne sont actuellement pas utilisées dans les populations :</p><ul>{$a}</ul></details>';
$string['continue_my_enrolments'] = 'Continuer mes inscriptions';
$string['copy_users_on_X_to'] = 'Copier les utilisateurs actuellement sur la {$a} vers';
$string['custom_welcome_message'] = 'Message de bienvenue personnalisé';
$string['custom_welcome_message_help'] = 'Les utilisateurs recevront un message de bienvenue par courriel lors de leur inscription.';
$string['date_diverging_from_calendar_date'] = 'Date divergente par rapport à la date du calendrier';
$string['default_enrolment_list'] = 'Liste d’inscription par défaut';
$string['default_enrolment_list_help'] = 'Détermine si les nouvelles inscriptions doivent être automatiquement acceptées. Ce réglage s’applique tant que les quotas ne sont pas atteints. Pour l’utilisation de l’option « Délai de paiement », la valeur de cette option doit être « {$a} ».';
$string['default_settings'] = 'Paramétrage par défaut';
$string['default_settings_description'] = 'Le paramétrage par défaut s’applique seulement aux <strong>nouvelles</strong> méthodes d’inscription créées. Le paramétrage ne s’applique pas aux méthodes déjà existantes.';
$string['default_value'] = 'Valeur par défaut : ';
$string['edit_field_X'] = 'Modifier le champ « {$a} »';
$string['enable_automatic_list_filling'] = 'Activer la remontée de liste automatique';
$string['enable_automatic_list_filling_help'] = 'Permet de faire remonter sur la liste par défaut ({$a->accepted} ou {$a->main} selon la configuration) un étudiant actuellement sur la {$a->wait} dès qu’une place s’y libère. L’action est appliquée seulement lorsqu’un étudiant se désinscrit en période d’inscription. Cette option n’est pas compatible avec l’option « Délai de paiement ».';
$string['enrolment_list'] = 'Liste d’inscription';
$string['enrolment_methods_overview'] = 'Vue d’ensemble des méthodes d’inscription';
$string['enrolment_to'] = 'Inscription en {$a}';
$string['enrolments_overview'] = 'Vue d’ensemble des inscriptions';
$string['enrolname'] = 'Nom de l’instance d’inscription';
$string['filters_for_managers'] = 'Filtres pour gestionnaires';
$string['inactive_enrolments'] = 'inactives (semestres précédents)';
$string['it_is_currently_not_possible_to_indicate_a_duration_greater_than_one_day'] = 'Pour des raisons techniques, il n’est pour le moment pas possible d’indiquer une durée supérieure à 27 heures.';
$string['list'] = 'Liste';
$string['list_of_courses_for_which_the_enrolment_method_will_be_changed'] = 'Liste des cours dont la méthode d’inscription sera modifiée :';
$string['lists'] = 'Listes';
$string['main_sport'] = 'Sport principal';
$string['manage_select_enrolments'] = 'Gérer les inscriptions par voeux';
$string['maximum_enrolments'] = 'Nombre d’inscriptions maximum';
$string['maximum_enrolments_must_be_greater_than_or_equal_to_minimum_enrolments'] = 'Le nombre d’inscriptions maximum doit être égal ou supérieur au nombre d’inscriptions minimum.';
$string['maximum_wishes'] = 'Nombre de voeux maximum';
$string['maximum_wishes_must_be_greater_than_or_equal_to_maximum_enrolments'] = 'Le nombre de voeux maximum doit être égal ou supérieur au nombre d’inscriptions maximum.';
$string['messageprovider:select_notification'] = 'Notifications en relation avec les inscriptions';

$string['minimum_enrolments'] = 'Nombre d’inscriptions minimum';
$string['no_available_cohorts'] = 'Aucune cohorte disponible';
$string['no_available_enrol_methods_desc'] = 'Pour utiliser cette fonctionnalité, une deuxième méthode d’inscription par voeux est nécessaire dans ce cours.';
$string['no_available_prices'] = 'Aucun tarif défini';
$string['no_available_roles'] = 'Aucun rôle disponible';
$string['no_dates'] = 'Aucune date';
$string['no_enrolment_method_available_with_these_selection_criteria'] = 'Aucune méthode d’inscription disponible avec ces critères de sélection';
$string['no_places_available'] = 'Aucune place disponible';
$string['no_quotas'] = 'Aucun quota';
$string['no_seat_restrictions'] = 'Aucune restriction de place';
$string['no_select_enrolment_method_uses_reenrolment_setting'] = 'Aucune méthode d’inscription par voeux n’utilise le paramètre « Instance de réinscription ».';
$string['number_of_wishes'] = 'Nombre de voeux';
$string['other_enrolment_number_X_type_Y'] = 'Autre inscription #{$a->number} ({$a->type})';
$string['overview'] = 'Vue d’ensemble';
$string['payment_deadline'] = 'Délai de paiement';
$string['payment_deadline_help'] = 'Ce paramètre accorde un délai de paiement à l’utilisateur. Si ce délai n’est pas respecté, APSOLU désinscrira automatiquement l’utilisateur du cours.

Pour désactiver cette fonctionnalité, il suffit de mettre la valeur de ce champ à 0.';
$string['payment_deadline_warning'] = '<p><strong>Attention : il vous reste {$a->deadline} pour payer. Passé ce délai, votre inscription sera automatiquement annulée !</strong></p>
<p>Si vous ne pouvez pas payer en ligne, merci de contacter votre secrétariat par téléphone ou par courriel à l’adresse {$a->contact}.</p>';
$string['place_remaining_on_listname_X'] = 'place restante sur la liste {$a}';
$string['places_remaining_on_listname_X'] = 'places restantes sur la liste {$a}';
$string['pluginname'] = 'Inscription par voeux';
$string['pluginname_desc'] = 'Le plugin d’inscription par voeux permet aux utilisateurs de choisir les cours qu’ils veulent suivre. Les cours peuvent être protégés par différents critères (période d’inscription, taille de la liste principale, cohortes, etc).';
$string['policyagree'] = 'J’atteste avoir pris connaissances de <a href="{$a}" target="blank_">ces recommandations médicales</a>.';
$string['reenrolment_disabled'] = 'Réinscription désactivée';
$string['selection_criteria'] = 'Critères de sélection';
$string['send_welcome_message_to_users_on_listname_X'] = 'Envoyer un message aux utilisateurs sur la liste {$a}';
$string['settings'] = 'Paramètres';
$string['the_delay_cannot_be_combined_with_the_automatic_list_filling'] = 'L’option « délai de paiement » ne peut pas être combinée avec l’option de « remontée de liste automatique ».';
$string['the_delay_cannot_be_set_to_a_value_of_less_than_20_minutes'] = 'L’option « délai de paiement » ne peut pas être définie à une valeur inférieure à 20 minutes.';
$string['the_delay_cannot_be_set_unless_default_list_is_accepted'] = 'L’option « délai de paiement » ne peut pas être définie si l’option « liste d’inscription par défaut » n’a pas la valeur « {$a} ».';
$string['the_field_welcome_message_seems_to_be_empty'] = 'Le champ « Message de bienvenue personnalisé » semble être est vide. Merci de confirmer ce choix en sélectionnant la valeur « Non » dans le champ « Envoyer un message aux utilisateurs ».';
$string['the_user_X_has_reached_their_wish_limit_for_the_role_Y'] = 'L’utilisateur #{$a->userid} a atteint sa limite de voeux pour le rôle #{$a->roleid}.';
$string['there_are_still_places_on_listname_X'] = 'Il reste des places sur la liste {$a}';
$string['unenrolment_from'] = 'Désinscription de {$a}';
$string['unenrolment_message'] = '<p>Bonjour,</p>
<p>Vous avez été désinscrit du cours {$a->coursename}.</p>
<p>Vous n’avez pas payé les frais d’inscription suivants :</p>
<ul>
    <li>{$a->cards}</li>
</ul>
<p>Au besoin, n’hésitez pas à nous contacter via l’adresse {$a->contact}.</p>
<p>Cordialement,</p>';
$string['unvalidated_enrolments'] = 'non validées ({$a->main} et {$a->wait})';
$string['warning_changing_calendar_may_result_in_loss_of_data'] = 'Attention ! La modification du calendrier peut entraîner une perte de données (comme par exemple les notes des étudiants).';
$string['welcome_messages'] = 'Messages de bienvenue';
$string['x_enrolment_methods_changed'] = '{$a} méthode(s) d’inscription modifiée(s)';
$string['x_enrolments_on_status_X'] = ' inscription(s) avec le statut {$a}';
$string['x_other_enrolment_s'] = '{$a} autre(s) inscription(s)';
$string['you_are_on_listname_X'] = 'Vous êtes sur la liste {$a}.';
$string['you_must_set_a_calendar_so_that_payments_can_apply'] = 'Vous devez définir un calendrier afin que les paiements puissent s’appliquer.';
$string['your_enrolment_has_been_registered'] = 'Votre inscription a été enregistrée.';
$string['your_wish_has_been_registered'] = 'Votre vœu a été enregistré.';

// Permissions.
$string['select:config'] = 'Configurer les instances d’inscription par voeux';
$string['select:enrol'] = 'Inscrire des utilisateurs';
$string['select:export'] = 'Exporter la liste des utilisateurs inscrits';
$string['select:manage'] = 'Gérer les utilisateurs inscrits';
$string['select:managepastenrolment'] = 'Gérer les utilisateurs inscrits sur des méthodes d’inscription expirées';
$string['select:unenrol'] = 'Désinscrire du cours les utilisateurs';

// Valeurs par défaut pour les libellés des noms de liste.
// Format "status".
$string['list_accepted'] = 'accepté';
$string['list_main'] = 'admissible';
$string['list_wait'] = 'complémentaire';
$string['list_deleted'] = 'refusé';

// Format "status abbr.".
$string['accepted_list_abbr'] = 'L.I.';
$string['main_list_abbr'] = 'L.P.';
$string['wait_list_abbr'] = 'L.C.';
$string['deleted_list_abbr'] = 'L.R.';

// Format "status short".
$string['accepted_list_short'] = 'Accep.';
$string['main_list_short'] = 'Admis.';
$string['wait_list_short'] = 'Compl.';
$string['deleted_list_short'] = 'Refus';

// Format "listname".
$string['accepted_list'] = 'des inscrits';
$string['main_list'] = 'principale';
$string['wait_list'] = 'complémentaire';
$string['deleted_list'] = 'de désinscription';

// Format "description".
$string['accepted_description'] = 'acceptés (inscription validée)';
$string['main_description'] = 'admissibles (en attente de validation)';
$string['wait_description'] = 'non admis (en attente d’un désistement)';
$string['deleted_description'] = 'désinscrits (inscription supprimée)';

$string['description_accessgranted_true'] = 'Ils ont accès aux forums et aux documents du cours';
$string['description_accessgranted_false'] = 'Ils n’ont accès ni aux forums, ni aux documents du cours';
$string['description_displaysession_first'] = 'Seule la première session du cours est indiquée sur leur page d’accueil.';
$string['description_displaysession_all'] = 'Une liste de toutes les sessions à venir est également indiquée sur leur page d’accueil.';
$string['description_displaysession_none'] = 'Ce cours n’est pas référencé sur leur page d’accueil.';

// Edit form.
$string['enableinstance'] = 'Activer cette méthode d’inscription';
$string['enroldate'] = 'Date des inscriptions';
$string['enrolstartdate'] = 'Date d’ouverture des inscriptions';
$string['enrolenddate'] = 'Date de fermeture des inscriptions';
$string['coursedate'] = 'Date des cours';
$string['coursestartdate'] = 'Date de début du cours';
$string['courseenddate'] = 'Date de fin du cours';
$string['reenroldate'] = 'Date des réinscriptions';
$string['reenrolstartdate'] = 'Date d’ouverture des réinscriptions';
$string['reenrolenddate'] = 'Date de fermeture des réinscriptions';
$string['reenrolinstance'] = 'Instance de réinscription';
$string['reenrolinstance_help'] = 'L’instance de réiniscription devrait toujours être configurée au premier semestre. Dans le menu déroulant, il faut cibler une instance du second semestre.';
$string['quotas'] = 'Quotas';
$string['enablequotas'] = 'Activer les quotas';
$string['cohorts'] = 'Cohortes';
$string['selectcohorts'] = 'Sélectionner les populations (cohortes)';
$string['registertype'] = 'Type d’inscription';

// Edit list form.
$string['default_reset_values'] = '<strong>Valeurs par défaut : </strong>laissez le champ vide lors de l’enregistrement pour réinitialiser la chaîne à sa valeur par défaut';
$string['description_strexample'] = 'état de l’inscription';
$string['description_strformat'] = 'étudiants {$a}';
$string['edit_description'] = 'Description';
$string['edit_description_help'] = 'Chaîne servant à redéfinir la description du statut de l’inscription. {$a}.';
$string['description_student'] = 'étudiants {$a}';
$string['edit_list_title'] = 'Modifier les chaînes pour la liste « {$a} »';
$string['edit_listname'] = 'Nom de la liste';
$string['edit_listname_help'] = 'Chaîne servant à renommer l’intitulé de la liste. {$a}.';
$string['edit_status'] = 'Statut d’inscription';
$string['edit_status_help'] = 'Chaîne servant à renommer le statut de l’inscription. {$a}.';
$string['edit_statusabbr'] = 'Acronyme';
$string['edit_statusshort'] = 'Version courte';
$string['formaterror'] = 'La valeur saisie doit respecter la formulation suivante : « {$a} »';
$string['listname_strexample'] = 'nom de la liste';
$string['listname_strformat'] = 'liste {$a}';
$string['reset_defaultvalues_ok'] = 'Les champs de la liste ont été repositionnés sur leurs valeurs par défaut.';
$string['savingvalues_ok'] = 'Les valeur associés à la liste {$a} ont été modifiées.';
$string['savingvalues_notok'] = 'Les valeur associés à cette liste n’ont pas été modifiées.';
$string['status_strformat'] = '« {$a} »';
$string['status_strexample'] = 'La valeur saisie doit être sous la forme suivante : « statut »';

$string['enrolenddateerror'] = 'La date de fin des inscriptions ne peut être antérieure à celle du début';
$string['courseenddateerror'] = 'La date de fin du cours ne peut être antérieure à celle du début';
$string['reenrolenddateerror'] = 'La date de fin des réinscriptions ne peut être antérieure à celle du début';
$string['reenrolstartdatemissingerror'] = 'La date de début des réinscriptions doit être renseignée si la date de fin est présente';
$string['reenrolenddatemissingerror'] = 'La date de fin des réinscriptions doit être renseignée si la date de début est présente';

$string['max_places'] = 'Nombre de places sur la liste {$a->accepted} et sur la liste {$a->main}';
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

$string['max_places_on_listname_X'] = 'Nombre de places sur la liste {$a}';
$string['role'] = 'Rôle attribué par défaut';

$string['status'] = 'Activer cette méthode d’inscription';
$string['general'] = 'Général';

$string['types'] = 'Cours évalué';
$string['wishes'] = 'Voeux';
$string['roles'] = 'Rôles';
$string['prices'] = 'Tarification';
$string['college'] = 'Population';
$string['colleges'] = 'Populations';
$string['renewals'] = 'Réinscriptions en masse';
$string['lists'] = 'Gestion des listes';

$string['error:enrol'] = 'Impossible de vous inscrire à ce cours';

$string['enrolment'] = 'Inscription';
$string['enrolments'] = 'Inscriptions';
$string['enrolmentsaved'] = 'Vœu enregistré';
$string['unenrolmentsaved'] = 'Désinscription effectuée';
$string['enrol'] = 'S’inscrire';
$string['unenrol'] = 'Se désinscrire';
$string['edit_enrol'] = 'Modifier son type d’inscription';
$string['change_course'] = 'Déplacer dans un autre cours';

$string['unenroled'] = 'Désinscrit';

$string['canntenrol'] = 'canntenrol';

$string['event_user_moved'] = 'Utilisateur déplacé';
$string['event_user_notified'] = 'Utilisateur notifié';
$string['full_registration'] = 'Inscription complète';

$string['maxwishes'] = 'Nombre de voeux maximum';
$string['maxwishes_help'] = 'Nombre de voeux maximum par défaut attribué à un utilisateur n’étant pas clairement identifié dans le système d’information ; n’appartenant à aucune population définie.';

// Gestion des voeux et des listes.
$string['editenroltype'] = 'Modifier le type d’inscription';
$string['enrolcoursesubject'] = '[{$a->fullname}] Situation de votre inscription';
$string['goto'] = 'Déplacer de la liste {$a->from} vers la liste {$a->to}';
$string['message'] = 'Message';
$string['message_greetings'] = 'Bonjour,' . PHP_EOL . PHP_EOL . '{$a}' . PHP_EOL . PHP_EOL . 'Cordialement,';
$string['message_moved_from_deleted'] = 'Vous avez été déplacé dans la liste {$a}.';
$string['message_moved_to_accepted'] = 'Votre pré-inscription a été confirmée.';
$string['message_moved_on_list'] = 'Vous avez été déplacé de la liste {$a->from} à la liste {$a->to}.';
$string['message_moved_to_next_accepted'] = 'Votre ré-inscription a été confirmée.';
$string['message_promote'] = 'Suite à un désistement, vous avez été placé sur la liste {$a}.';
$string['move_next_on_listname_X'] = 'Réinscrire dans la liste {$a}';
$string['move_on_listname_X'] = 'Déplacer dans la liste {$a}';
$string['move_to'] = 'Déplacer';
$string['next_on_listname_X'] = '{$a} du prochain semestre';
$string['notify'] = 'Notifier par email';
$string['notifystudents'] = 'Notifier les étudiants';
$string['previous_on_listname_X'] = '{$a} du semestre précédent';
$string['send_message'] = 'Envoyer un message';


$string['eula'] = 'Certificat d’aptitude au sport';
$string['eula_help'] = 'Texte présenté à tous les étudiants avant toute inscription à une activité sportive et nécessitant une acceptation de leur part.';

// Manage.
$string['xls_export'] = 'Exporter au format Excel';
$string['lockedform'] = 'Semestre verrouillé';
$string['no_users'] = 'Aucun utilisateur dans cette liste';
$string['select'] = 'Sélectionner';
$string['lmd'] = 'LMD';
$string['all_registers'] = 'Toutes les inscriptions';
$string['register_date'] = 'Date d’inscription';

// Variables pour l'export csv des listes étudiantes.
$string['age'] = 'Âge';
$string['birthday'] = 'Date de naissance';
$string['sex'] = 'Sexe';
$string['register_type'] = 'Type d’inscription';
$string['paid'] = 'Carte sport payée';

// Licence FFSU.
$string['federation_required'] = 'Adhésion à l’association sportive (obligatoire)';
$string['federation_required_help'] = 'L’adhésion à l’association sportive permet de faire des compétitions en dehors des heures de cours. Elle coûte 15€ et vous fait adhérer automatiquement à la Fédération Française des Sports Universitaires (FFSU)';
$string['federation_optional'] = 'Adhésion à l’association sportive (facultatif)';
$string['federation_optional_help'] = $string['federation_required_help'];

$string['error_no_left_slot'] = 'Il n’y a pas plus de place disponible pour ce cours.';
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
$string['reenrolmentexplanationcase'] = '<div class="alert alert-info">' .
    '<ol>' .
    '<li>vous souhaitez poursuivre sur le même créneau, il vous suffit de compléter et d’enregistrer le tableau ci-dessous</li>' .
    '<li>vous souhaitez changer de créneau avec le même enseignant, <strong>contactez-le vite par mail <u>avant le {$a->limit}</u></strong></li>' .
    '<li>vous souhaitez vous inscrire sur un autre cours avec un nouvel enseignant, revenez vous préinscrire sur « votre espace SIUAPS » à partir du <strong>{$a->from}</strong></li>' .
    '</ol>' .
    '</div>';
$string['reenrolmentexplanationcasenoenrol'] = '<div class="alert alert-info">' .
    '<ol>' .
    '<li>vous souhaitez poursuivre sur le même créneau, il vous suffit de compléter et d’enregistrer le tableau ci-dessous</li>' .
    '<li>vous souhaitez changer de créneau avec le même enseignant, <strong>contactez-le vite par mail <u>avant le {$a->limit}</u></strong></li>' .
    '</ol>' .
    '</div>';
$string['coursename'] = 'Nom du cours';
$string['teachercontact'] = 'Contact enseignant';
$string['renewenrolement'] = 'Renouveler mon inscription';
$string['savedreenrolment'] = 'Votre choix a été enregistré.<br />Vous pouvez revenir sur votre sélection à tout moment jusqu’au {$a->date}.';
$string['reenrolmentnotificationsubject'] = 'Récapitulatif de vos réinscriptions au SIUAPS';
$string['reenrolmentnotification'] = 'Bonjour,' . PHP_EOL . PHP_EOL .
    'Vous avez choisi de :' . PHP_EOL .
    '{$a->choices}' . PHP_EOL . PHP_EOL .
    'En cas de demande de réinscription, il vous appartient maintenant de vous présenter (avec votre tenue) sur le lieu et à l’heure du cours lors de la semaine de rentrée au SIUAPS - voir « mes rendez-vous à venir »' . PHP_EOL . PHP_EOL .
    'À bientôt,' . PHP_EOL . PHP_EOL .
    'L’équipe du SIUAPS';
$string['reenrolmentcontinue'] = 'poursuivre le cours {$a->fullname}';
$string['reenrolmentstop'] = 'quitter le cours {$a->fullname}';

// Debug.
$string['debug_enrol_invalid_enrolment'] = 'Le cours #{$a->courseid} n’est pas un créneau apsolu. La méthode d’inscription #{$a->enrolid} a été ignorée.';
$string['debug_enrol_invalid_category'] = 'Le cours #{$a->courseid} n’est pas rattaché à une activité sportive apsolu (catégorie #{$a->categoryid}.';
$string['debug_enrol_no_enrolments'] = 'Le cours #{$a->courseid} n’offre aucune méthode d’inscription par voeux valide pour l’utilisateur #{$a->userid}.';
$string['debug_enrol_too_many_enrolments'] = 'Le cours #{$a->courseid} offre plus d’une méthode d’inscription par voeux valide pour l’utilisateur #{$a->userid}.';


// Statistiques.
$string['statistics_enrollee_on_status_X'] = 'Candidat {$a}';
$string['statistics_enrollee_wish_list'] = 'Candidat Vœux';
$string['statistics_enrolment_on_statusshort_X'] = 'Inscription {$a}';
$string['statistics_enrollees'] = "Inscrits en activité physique";
$string['statistics_enrollees_on_status_X'] = 'Inscrits en activité physique avec le statut {$a}';
$string['statistics_enrolments'] = "Inscriptions aux activités physiques";
$string['statistics_enrolments_on_status_X'] = 'Inscriptions aux activités physiques avec le statut {$a}';
$string['statistics_enrollees_complementary'] = "Inscrits en activité complémentaire";
$string['statistics_enrolments_complementary'] = "Inscriptions aux activité complémentaire";
$string['statistics_on_status_X_accepted'] = 'Proportion d’inscrits ayant obtenu le statut {$a} sur au moins une inscription';
$string['statistics_enrollees_on_status_X_accepted'] = 'Inscrits ayant obtenu le statut {$a} sur au moins une inscription';
$string['statistics_enrollees_on_status_X_accepted_tooltip'] = 'Affiche la liste des personnes ayant obtenu le statut {$a} sur au moins une inscription';
$string['statistics_on_status_X_deleted'] = 'Candidats ayant obtenu le statut {$a} pour toutes ses inscriptions';
$string['statistics_on_status_X_evaluated'] = 'Inscriptions avec le rôle "évalué" ayant obtenu le statut {$a}';
$string['statistics_enrolment_nb_on_status_X'] = 'Ayant obtenu le statut {$a}';
$string['statistics_quota_on_listname_X'] = 'Quota liste {$a}';
