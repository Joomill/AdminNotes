# Changelog

All notable changes to the Extension are documented in this file.

## [1.5.0] - Unreleased
- Addition: help buttons now link to the Joomill documentation page (https://www.joomill-extensions.com/documentation/admin-notes-module)
- Improvement: installer script rebuilt on InstallerScriptInterface (Joomla 4.2+ modern path) with quickstart links on the install screen
- Addition: review request banner in the module settings linking to the JED listing

## TODO

### Roadmap 1.6.0 - vangnet en context bij het opslaan
- Change: minimum Joomla versie naar 6.1 (manifest, script.php $minimumJoomlaVersion, JED-listing)
- Change: saveData() opslaan via de com_modules ModuleModel in plaats van directe DB-update, zodat core module versioning (Joomla 6.1, PR joomla-cms#46772) de notitie-historie bijhoudt
- Addition: "laatst bewerkt door/op" onder de notitie (gebruiker-id + timestamp opslaan in module-params bij saveData())
- Addition: link naar de Versions-historie (module-bewerkscherm) tonen bij de notitie voor gebruikers met bewerkrechten
- Let op: historie werkt alleen als save_history aanstaat in com_modules (standaard uit, limiet 10) en com_contenthistory actief is; vermelden in documentatie en eventueel als note-veld in de module-instellingen

### Roadmap 1.7.0 - conceptbescherming
- Addition: editor-inhoud bufferen in localStorage en terugzetten na mislukte save (verlopen sessie, dichtgeklikt tabblad)
- Optioneel uit te bouwen naar AJAX-save als de minimale variant bevalt

### Roadmap 1.8.0 - privé-notities (alleen bij duidelijke vraag)
- Addition: scope-instelling gedeeld/persoonlijk; bij persoonlijk ziet elke beheerder zijn eigen notitie
- Let op: breekt het huidige datamodel (content in #__modules); per-gebruiker opslag eerst ontwerpen voordat er gebouwd wordt

