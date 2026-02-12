# Stratégie Marketing - Faktur.lu

## 1. Certifications et Validations Officielles

### FAIA (Fichier Audit Informatisé AED)

**Situation actuelle :**
- Il n'existe **pas de certification officielle** pour les logiciels générant des fichiers FAIA
- L'[AED (Administration de l'Enregistrement et des Domaines)](https://pfi.public.lu/fr/professionnel/tva/faia.html) publie uniquement des **spécifications techniques** (schéma XSD FAIA 2.01)
- La validation se fait **lors d'un contrôle fiscal** - si le fichier est invalide, c'est un problème

**Stratégie recommandée :**

1. **Auto-validation technique**
   - Valider les fichiers XML contre le [schéma XSD officiel FAIA 2.01](https://pfi.public.lu/fr/professionnel/tva/faia/faia-201.html)
   - Créer une suite de tests avec des cas réels

2. **Validation par un cabinet reconnu**
   - Faire auditer la génération FAIA par un cabinet comme [PwC Luxembourg](https://www.pwc.lu/en/vat/docs/pwc-indirect-tax-is-your-faia-file-ready-for-scrutiny-fr.pdf) ou Deloitte
   - Obtenir une **attestation de conformité** (même si non-officielle, c'est un gage de confiance)

3. **Contact direct AED**
   - Soumettre des fichiers test à l'AED pour validation informelle
   - Demander un avis technique écrit

---

### Peppol (Facturation électronique B2G/B2B)

**Situation actuelle :**
- Au Luxembourg, Peppol B2G est **obligatoire depuis 2023** pour toutes les entreprises
- Il faut passer par un **Access Point certifié** pour envoyer/recevoir des factures

**Options :**

| Option | Coût | Complexité | Recommandation |
|--------|------|------------|----------------|
| **Devenir Access Point** | ~15-30K€/an + ISO 27001 | Très élevée | Non recommandé |
| **Partenariat Access Point** | Variable | Moyenne | **Recommandé** |
| **API tierce (Storecove, Pagero)** | ~0.20-0.50€/facture | Faible | **Idéal pour démarrer** |

**Access Points certifiés au Luxembourg :**
- [Digiteal](https://www.digiteal.eu/peppol-access-point/) (Belgique/Luxembourg)
- [Storecove](https://www.storecove.com/blog/en/peppol-access-point/)
- [Pagero (Thomson Reuters)](https://www.pagero.com/compliance/peppol)
- [EDICOM](https://edicomgroup.com/peppol)

**Recommandation :** Intégrer l'API de **Storecove** ou **Digiteal** - ils gèrent la certification Peppol, vous n'avez qu'à envoyer les données via leur API.

---

## 2. Stratégie Marketing et Acquisition

### Phase 1 : Crédibilité (Mois 1-3)

| Action | Détail | Budget |
|--------|--------|--------|
| **Partenariat fiduciaire pilote** | 2-3 fiduciaires testent gratuitement et donnent un témoignage | 0€ |
| **Audit FAIA** | Attestation PwC ou Deloitte | 3-5K€ |
| **Présence House of Startups** | Intégrer [LHoFT](https://lhoft.com/) ou [LCI](https://www.host.lu/) | ~500€/mois |
| **Site web optimisé SEO** | Focus mots-clés : "logiciel facturation Luxembourg", "FAIA", "Peppol" | 0€ |

### Phase 2 : Acquisition (Mois 3-6)

| Canal | Stratégie | Coût estimé |
|-------|-----------|-------------|
| **Partenariats fiduciaires** | Commission 10-20% récurrent ou licence gratuite pour leurs clients | Performance |
| **Google Ads** | Mots-clés ciblés Luxembourg (marché petit = CPC raisonnable) | 500-1000€/mois |
| **LinkedIn Ads** | Ciblage gérants PME, comptables Luxembourg | 500€/mois |
| **Content Marketing** | Blog : guides TVA Luxembourg, FAIA, obligations légales | 0€ |
| **[SME Digital Package](https://www.houseofentrepreneurship.lu/)** | Subvention 5000€ pour digitalisation PME - être référencé | 0€ |

### Phase 3 : Scale (Mois 6-12)

| Action | Impact |
|--------|--------|
| **Événements Chambre de Commerce** | Networking décideurs PME |
| **Webinaires** | "Comment être conforme FAIA en 2026" |
| **Programme de parrainage** | 1 mois gratuit par client référé |
| **Intégration comptable** | API vers Sage BOB, FID-Manager |

---

## 3. Positionnement Différenciateur

**Message clé suggéré :**

> "Le seul logiciel de facturation **conçu au Luxembourg, pour le Luxembourg** - conforme FAIA, Peppol ready, avec support en français, allemand et luxembourgeois."

**Arguments différenciants :**
- 🇱🇺 Made in Luxembourg (vs Sage, QuickBooks, etc.)
- ✅ FAIA natif (pas un module ajouté)
- 📧 Peppol intégré pour B2G
- 🔒 Données hébergées au Luxembourg/EU
- 💬 Support local multilingue

---

## 4. Fonctionnalités à Mettre en Avant (SEO)

### Facturation
- Création de factures professionnelles
- Numérotation automatique conforme
- Multi-devises (EUR, USD, CHF)
- TVA automatique selon scénarios luxembourgeois
- Notes de crédit / Avoirs
- Factures récurrentes
- Envoi par email intégré
- Export PDF/A pour archivage légal

### Conformité Luxembourg
- Export FAIA 2.01 pour contrôles fiscaux
- Peppol pour facturation B2G
- Livre des recettes conforme AED
- Archivage PDF/A 10 ans
- Audit trail complet

### Gestion Clients
- Fiche client complète
- Historique des factures
- Intracommunautaire / Export / National
- Validation TVA automatique (VIES)

### Gestion de Projets
- Projets avec tâches et sous-tâches
- Vue Liste, Kanban, Timeline
- Suivi du temps passé
- Budget heures vs réel

### Time Tracking
- Timer intégré
- Entrées manuelles
- Liaison projet/tâche
- Rapports par client/projet

### Dépenses
- Saisie des dépenses
- Catégorisation
- TVA déductible
- Export comptable

### Tableau de Bord
- KPIs en temps réel
- CA mensuel/annuel
- Factures en attente
- Top clients

### Multi-utilisateurs
- Accès comptable en lecture
- Invitations par email
- Gestion des rôles

### Sécurité
- Authentification 2FA
- Chiffrement des données
- Hébergement EU (RGPD)
- Sauvegardes automatiques

---

## 5. Plan d'Action Prioritaire

### Cette semaine
- [ ] Contacter 2-3 fiduciaires pour un pilote gratuit
- [ ] Identifier un Access Point Peppol (Digiteal ou Storecove)

### Ce mois
- [ ] Demander un devis audit FAIA à PwC/Deloitte
- [ ] Candidater à [House of Startups](https://www.host.lu/)
- [ ] Optimiser landing page SEO

### Prochain trimestre
- [ ] Intégration Peppol via API partenaire
- [ ] Lancement officiel avec témoignages fiduciaires
- [ ] Campagne Google/LinkedIn Ads
- [ ] Blog avec articles SEO

---

## 6. Budget Marketing Estimé (Année 1)

| Poste | Budget |
|-------|--------|
| Audit FAIA (PwC/Deloitte) | 3-5K€ |
| House of Startups | 6K€ |
| Google Ads | 6-12K€ |
| LinkedIn Ads | 6K€ |
| Événements/Networking | 2K€ |
| **Total** | **23-31K€** |

---

## 7. KPIs à Suivre

| Métrique | Objectif M6 | Objectif M12 |
|----------|-------------|--------------|
| Visiteurs site/mois | 1 000 | 5 000 |
| Inscriptions trial | 50 | 200 |
| Clients payants | 20 | 100 |
| MRR | 1 000€ | 5 000€ |
| Churn mensuel | < 5% | < 3% |
| NPS | > 40 | > 50 |

---

## Sources et Ressources

- [Portail Fiscalité Indirecte - FAIA](https://pfi.public.lu/fr/professionnel/tva/faia.html)
- [OpenPeppol Certified Providers](https://peppol.org/members/peppol-certified-service-providers/)
- [House of Startups Luxembourg](https://www.host.lu/)
- [LHoFT - FinTech Hub](https://lhoft.com/)
- [Digiteal Peppol Access Point](https://www.digiteal.eu/peppol-access-point/)
- [Storecove Peppol](https://www.storecove.com/blog/en/peppol-access-point/)
- [SME Digital Package](https://www.houseofentrepreneurship.lu/)
