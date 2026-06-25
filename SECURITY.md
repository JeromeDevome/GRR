# Sécurité

Pour nous, la sécurité de nos utilisateurs et de leurs données est une priorité. Nous mettons tout en œuvre pour identifier, corriger et prévenir les vulnérabilités de sécurité.

Cette page décrit la procédure de signalement des vulnérabilités, leur traitement ainsi que notre politique de divulgation responsable.

## Signaler une vulnérabilité

Si vous découvrez une vulnérabilité de sécurité dans GRR, nous vous invitons à nous la signaler de manière responsable.

**Merci de ne pas publier publiquement la vulnérabilité avant de nous avoir contactés.**

Les signalements doivent être adressés par la plateforme **"Report a vulnerability"** de GitHub (attention, ne créez pas d'issue publique).

Afin de faciliter l'analyse, merci de fournir :

- Une description détaillée de la vulnérabilité.
- Les étapes permettant de reproduire le problème.
- L’impact potentiel sur les utilisateurs ou les données.
- Toute preuve de concept (PoC) pertinente.
- La version de GRR concernée.

Pour protéger nos utilisateurs, nous vous demandons de nous laisser un délai raisonnable, idéalement **90 jours**, avant toute divulgation publique. Nous nous engageons à accuser réception de votre signalement dans les meilleurs délais et à vous tenir informé de son traitement.

## Vulnérabilités non éligibles

Les signalements suivants ne sont généralement pas considérés comme des vulnérabilités de sécurité dans GRR :

- Résultats issus uniquement d’outils automatiques ou de scanners sans démonstration exploitable.
- Attaques théoriques sans preuve d’exploitation réaliste.
- Vulnérabilités provenant exclusivement d’une bibliothèque ou d’un logiciel tiers.
- Attaques reposant sur l’ingénierie sociale.
- Attaques nécessitant un accès administrateur au serveur hébergeant GRR.
- Attaques nécessitant un accès physique à l’équipement ou au réseau de l’utilisateur.
- Attaques nécessitant l’installation volontaire d’un logiciel ou d’un module malveillant.
- Actions qu’un utilisateur ne peut réaliser que sur sa propre instance GRR.
- Problèmes liés à une mauvaise configuration volontairement mise en place par l’administrateur.

## Versions prises en charge

Nous acceptons les signalements concernant :

- La dernière version stable officielle de GRR.
- Les versions en cours de développement ou de test (bêta, RC).

Les vulnérabilités affectant des versions obsolètes ou modifiées par des tiers pourront ne pas être prises en charge.

## Évaluation de la sévérité

Si vous êtes familier avec le standard **CVSS v3.1 ou v4.0**, vous pouvez joindre une estimation de la gravité de la vulnérabilité sous forme de vecteur CVSS.

Si vous ne connaissez pas ce système de notation, ce n'est pas un problème : notre équipe évaluera elle-même la criticité du signalement.

## Divulgation publique et attribution CVE

Lorsqu'une vulnérabilité est confirmée et corrigée, nous pouvons publier un avis de sécurité décrivant :

- La nature de la vulnérabilité.
- Les versions affectées.
- Les versions corrigées.
- Les mesures de remédiation recommandées.

Pour les vulnérabilités présentant un impact significatif, une demande d'attribution d'un identifiant **CVE (Common Vulnerabilities and Exposures)** pourra être effectuée.

## Récompenses

GRR étant un projet open source, nous ne proposons actuellement pas de programme de récompense financière (bug bounty).

Toutefois, sauf demande contraire de votre part, nous serons heureux de mentionner et remercier les personnes ayant contribué à améliorer la sécurité du projet.

## Avis de sécurité publiés

Les avis de sécurité publiés concernant GRR seront listés dans cette section afin de permettre aux utilisateurs de suivre l'historique des correctifs de sécurité.
