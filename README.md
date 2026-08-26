# ScanDetect-CEEC — version mise à jour

## Contenu du site

Les textes du dossier `Copyright/Copyright - ScanDetect.docx` sont intégrés dans les pages :

- Page d'accueil : **ScanDetect par CEEC** / **Escorte numérique certifiée de vos minéraux** et le texte de présentation.
- Connexion : **Espace sécurisé**, **Bienvenue**, **Connectez-vous pour générer des certificats**.
- Tableau de bord : certificats générés et génération d'un certificat.
- Formulaire : 30 champs répartis en 3 sections.
- Confirmation : vérification avant génération.
- Résultat : message de succès et instruction d'impression.
- Copyright : `© 2026 ScanDetect par CEEC — Centre d’Expertise, d’Évaluation et de Certification`.

## Identité visuelle

- Logo utilisé : `images/scandetect-logo.png`, préparé à partir du logo fourni dans le dossier `PNG`.
- Couleurs principales reprises du logo : bleu ScanDetect, bleu clair et jaune.
- Police du site : pile typographique proche du lettrage du logo (`Century Gothic`, `Avenir Next`, `Futura`, puis polices de secours).
- Images de thème de la page d'accueil : `images/home-hero.jpg` et `images/home-tracking.jpg`, optimisées à partir des images fournies.

## Comptes initiaux

- `admin@scandetect-ceec.cd` — mot de passe initial : `ChangeMe123!`
- `jb.otshudi@scandetect-ceec.cd` — mot de passe initial : `ScanDetectCEEC`

Les mots de passe sont stockés sous forme de hash dans `includes/config.php`.

## Fonctionnement du certificat

Le code ne reconstruit pas le design institutionnel : il utilise directement le **nouveau modèle officiel** fourni dans `New modele Certificat d'origine à l'exportation.pdf`, également copié dans `templates/certificat-source.pdf`.

1. Le nouveau fichier maître contient 4 pages ; la **page 2** sert de fond officiel du certificat généré.
2. Une image haute résolution de cette page est utilisée comme fond : `images/certificate-template-pdf2.png`.
3. Le format du nouveau certificat est respecté à l'identique : **366 × 210 mm**.
4. Le système ajoute uniquement les données dynamiques :
   - les 30 réponses, en noir ;
   - le numéro du certificat dans le tableau, en grand vertical, sur le volet gauche et sur la vignette droite ;
   - les **3 QR** prévus par le nouveau modèle : QR blanc sur le volet gauche, QR bleu sur le corps du certificat et QR blanc sur la vignette droite.
5. La correction déjà validée de la question 29 est conservée : `CEEC Representative` remplace visuellement `EEC Representative` dans le PDF généré.
6. Le PDF de vérification et le PDF d'impression reprennent tous les éléments du nouveau modèle complet.

### Positions d'impression

Les coordonnées ont été recalées sur le nouveau PDF source : les 30 réponses, les 4 occurrences du numéro et les 3 QR sont positionnés sur les emplacements du modèle sans redessiner les éléments institutionnels.

## Dossiers de stockage

- `storage/certificates/` : données JSON des certificats.
- `storage/generated/` : PDF d’impression complet sur le nouveau modèle officiel.
- `storage/verification/` : PDF complet de vérification.

Le dossier `storage/` est protégé par `.htaccess`.

## Domaine et phase de test

`APP_BASE_URL` est volontairement vide dans `includes/config.php`.

Le système détermine automatiquement le domaine courant :

- pendant la phase de test sur `rarsm.org`, les QR utiliseront l'URL de test réelle ;
- après transfert sur `scandetect-ceec.cd`, les QR utiliseront automatiquement ce domaine.

Cela évite de régénérer le code au moment du changement de domaine.

## Installation

1. Téléverser le contenu du dossier sur le serveur.
2. Vérifier que PHP peut écrire dans :
   - `storage/certificates/`
   - `storage/generated/`
   - `storage/verification/`
3. Permissions recommandées : dossiers `755` ou `775` selon l'hébergeur, fichiers `644`.
4. Ouvrir la page d'accueil, puis se connecter.
5. Remplir les 30 champs et confirmer.
6. Attendre le message indiquant que les PDF sont prêts.
7. Imprimer le PDF d’impression complet selon le nouveau modèle officiel.

## Important sur les QR blancs

Les QR du volet gauche et de la vignette droite sont générés en blanc et sans fond afin de s’intégrer dans les zones bleues du nouveau modèle. Le QR du corps du certificat est généré en bleu ScanDetect sur fond transparent.

## Sélection et statut des numéros de certificat

Le champ **Numéro du certificat** est maintenant une liste déroulante.

- 🔴 **Rouge — Non traité** : le numéro figure dans la liste officielle mais aucun dossier n'a encore été créé.
- 🟠 **Orange — En cours de traitement** : des données existent, mais le certificat n'a pas encore terminé le cycle vérification + impression.
- 🟢 **Vert — Vérifié et imprimé** : les PDF ont été générés et l'action d'impression a été enregistrée.

La liste officielle se trouve dans :

`storage/certificate-numbers.txt`

Ajouter **un numéro par ligne**. Les zéros au début des numéros sont conservés (ex. `00007`). Les numéros déjà présents dans les certificats enregistrés sont ajoutés automatiquement à la liste, même s'ils ne figurent pas encore dans ce fichier.

Lorsqu'un numéro est sélectionné :

- un certificat rouge affiche les 29 autres rubriques vides, prêtes à être complétées ;
- un certificat orange recharge automatiquement toutes les données déjà enregistrées afin de poursuivre le traitement ;
- un certificat vert recharge toutes les données en lecture seule et propose l'accès au certificat complet.

**Déploiement :** ne pas supprimer les fichiers déjà présents dans `storage/certificates`, `storage/generated` et `storage/verification` lors de la mise à jour du site.


## Données de démonstration ajoutées
- 00007 : vert — vérifié et imprimé (données préremplies)
- 00008 : orange — en cours de traitement (données préremplies)
- 00009 : vert — vérifié et imprimé (données préremplies)
- 00010 : orange — en cours de traitement (données préremplies)
- 00011 : rouge — non traité (aucune donnée)
- 00012 : rouge — non traité (aucune donnée)
Ces entrées sont explicitement marquées comme données de démonstration dans les fichiers JSON.

## Validation OTP avant génération

La page de confirmation utilise désormais un bouton **Valider**. Au clic :

1. un modal de confirmation s'ouvre ;
2. un OTP à 6 chiffres est généré côté serveur ;
3. l'OTP est envoyé à l'adresse e-mail associée au représentant du **Ministère des Mines** sélectionné ;
4. le code expire après 10 minutes, avec 5 tentatives maximum et un délai de 60 secondes avant renvoi ;
5. `generate-pdf.php` refuse toute génération tant que l'OTP n'a pas été confirmé côté serveur.

Les représentants et adresses sont configurés dans :

`includes/representatives.php`

**Important :** les trois adresses e-mail du responsable de mine n'ayant pas encore été fournies, les trois choix utilisent temporairement `webmaster@scandetect-ceec.cd` pour recevoir les OTP de test. Remplacer ces adresses par les adresses officielles avant la mise en production.

Les OTP sont envoyés avec l'expéditeur `ScanDetect-CEEC <noreply@scandetect-ceec.cd>` et une adresse de réponse `noreply@scandetect-ceec.cd`. L'envoi de production utilise la fonction PHP `mail()`. L'hébergement doit donc autoriser l'envoi d'e-mails pour `scandetect-ceec.cd`. Si un SMTP authentifié doit être utilisé, il faudra renseigner ses paramètres (serveur, port, utilisateur et mot de passe).

En environnement local (`localhost` / `127.0.0.1`), l'OTP est affiché dans le modal uniquement pour faciliter les tests.


## Envoi OTP SMTP depuis localhost

Les OTP utilisent désormais le vrai SMTP :
- Serveur : `mail.scandetect-ceec.cd`
- Port : `465`
- Sécurité : SSL/TLS
- Compte SMTP : `noreply@scandetect-ceec.cd`
- Destinataire de test : `webmaster@scandetect-ceec.cd`

Avant de tester, ouvrez `includes/mail-secrets.local.php` et renseignez le mot de passe réel du compte `noreply@scandetect-ceec.cd`. Le code OTP n'est plus affiché dans le modal local : il doit être reçu par e-mail.

Pour éviter de conserver le mot de passe dans un fichier, vous pouvez aussi définir la variable d'environnement `SCANDETECT_SMTP_PASSWORD`.

## Mise à jour — tableau de bord et verrouillage après impression
- Le bouton « Tableau de bord » est placé en bas des écrans de saisie/génération concernés.
- Le tableau de bord contient désormais les colonnes « Statut » et « Localisation du camion ».
- La localisation affichée correspond à la dernière position déclarée dans le dossier (sortie de frontière, puis poste de sortie, transit ou lieu de délivrance), sauf si une valeur `truck_location` explicite existe dans l'enregistrement.
- Un certificat avec `printed_at` est considéré comme définitivement « Imprimé ».
- Une ligne « Imprimé » n'est pas cliquable et ne propose que la consultation du PDF de vérification.
- `generate-pdf.php`, `save-generated-pdf.php`, `mark-printed.php` et `certificate-pdf.php?type=print` bloquent toute nouvelle génération/réimpression d'un certificat déjà imprimé.

## Mode MOCK intégré dans le code

Cette version contient des certificats de test directement dans `includes/mock-data.php`.
Le mode est contrôlé par `SCANDETECT_MOCK_DATA_ENABLED` dans `includes/config.php`.

- `true` : affiche les données MOCK 00007 à 00010.
- `false` : masque totalement les données MOCK et n'utilise que les certificats réels de `storage/certificates`.

Les données réelles ont toujours priorité : si un vrai certificat possède le même numéro, il remplace automatiquement le MOCK correspondant.
Les numéros 00011 et 00012 restent disponibles comme certificats rouges/non traités grâce à `storage/certificate-numbers.txt`.

## Correctif chemins local / production et formulaire vide

Cette version centralise les chemins internes avec `app_route()` et détecte automatiquement si ScanDetect est installé à la racine du domaine ou dans un sous-dossier local.

- `certificat.php` précharge côté serveur le certificat ouvert depuis le tableau de bord.
- Si l'appel AJAX à `certificate-data.php` est bloqué ou retourne autre chose que du JSON, l'interface recharge automatiquement `certificat.php?number=...` et les données sont rendues par PHP.
- Le bouton `Voir` du tableau passe par `view-certificate.php`. Si le PDF de vérification existe, il est ouvert ; sinon un aperçu en lecture seule est affiché à partir des données du certificat.
- Les endpoints OTP, génération PDF, sauvegarde PDF et verrouillage d'impression utilisent désormais les chemins calculés par l'application.
- `diagnostic.php` affiche la version PHP, le chemin d'installation détecté, les permissions de stockage et la présence des routes essentielles.
- Le chargement des enregistrements est mis en cache pendant une requête afin d'éviter des lectures répétées du dossier `storage` lorsque la liste des certificats devient plus grande.

Lors d'une mise à jour en production, conserver impérativement les données réelles déjà présentes dans `storage/certificates`, `storage/generated`, `storage/verification` et votre liste réelle `storage/certificate-numbers.txt`.

## Mise à jour - carte, nouveau modèle PDF et double réception OTP

- La page de connexion utilise désormais des phrases commençant par une majuscule.
- Dans le tableau de bord, la cellule « Localisation du camion » est cliquable et ouvre un modal contenant le schéma de traçabilité fourni dans `map localisation.pdf`. Une image optimisée est utilisée dans le modal : `images/map-localisation.png`.
- Le modèle maître du certificat a été remplacé par `New modele Certificat d'origine à l'exportation.pdf` et copié dans `templates/certificat-source.pdf`.
- La page 2 du nouveau modèle sert de fond au certificat généré. Le format du certificat est désormais 366 x 210 mm, conformément au PDF source.
- Le système positionne les 30 réponses, le numéro aux quatre emplacements du nouveau modèle et les trois QR prévus par ce modèle.
- La correction déjà validée « CEEC Representative » est conservée dans le PDF généré, même si le fond source contient encore « EEC Representative ».
- Pendant cette phase de test, le même OTP est envoyé dans une seule transaction SMTP à :
  - `webmaster@scandetect-ceec.cd`
  - `admin@scandetect-ceec.cd`
  L'expéditeur reste `noreply@scandetect-ceec.cd`.
