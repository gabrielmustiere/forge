# Glossaire — squelette et exemples

Référence de `/forge:glossary`. Le squelette fixe la forme du fichier ; les exemples montrent la
différence entre une définition écrite pour un lecteur de l'interface et une définition écrite
depuis le code. Les exemples prennent une application de facturation imaginaire : ne les recopie
pas, transpose-les.

## Squelette

```markdown
# Glossaire — <Nom de l'application tel qu'il s'affiche>

> <Une phrase : ce que contient ce glossaire et pour qui il est écrit.>

_Document vivant — à tenir à jour quand une notion apparaît ou change dans l'application. Date de dernière mise à jour : AAAA-MM-JJ._

<Deux phrases : à quoi sert l'application, et pour qui.>

## <Premier thème rencontré — souvent : les personnes et leurs rôles>

- **<Terme>** — <définition>.
- **<Terme>** — <définition>. <Qui le voit / qui peut agir, si cela dépend du rôle.>
  - **<Valeur>** — <ce qu'elle signifie>.
  - **<Valeur>** — <ce qu'elle signifie>.

## <Thème suivant, dans l'ordre de découverte>

- **<Terme>** — <définition>.
- **<Terme>** _(à venir)_ — <définition>. *(seulement si l'utilisateur l'a demandé pour une notion en cours)*

## Menus et navigation

- **<Menu ou groupe>** — <ce qu'on y trouve>. <Qui le voit.>
  - **<Entrée>** — <la page atteinte et ce qu'on y fait>.
```

Hors de cette forme, rien : ni table des matières, ni index alphabétique, ni changelog. L'historique
du fichier est dans git ; sa fraîcheur tient à la date de l'en-tête.

## Ordre des sections

L'ordre suit le parcours d'un utilisateur qui découvre l'application, pas l'alphabet ni le modèle
de données. Pour une application de facturation, par exemple :

1. Personnes et rôles (qui se connecte, avec quels droits)
2. Clients
3. Devis et factures (les objets principaux)
4. Cycle de vie d'une facture (ses statuts)
5. Paiements et relances (les actions)
6. Indicateurs du tableau de bord
7. Menus et navigation

## Exemples

### Le registre : l'interface, pas le code

❌ `` - **Facture** — entité `Invoice` (table `invoice`) liée à un `Customer`, avec un `InvoiceStatus`. ``

✅ `- **Facture** — le document envoyé à un client pour lui demander un paiement. Elle porte un numéro, une date d'échéance et un statut.`

### Le libellé exact

La spec de conception parle d'« avoir », l'écran affiche « Note de crédit » :

❌ `- **Avoir** — …`

✅ `- **Note de crédit** — …` et l'écart part dans les incohérences du compte rendu.

### Les valeurs d'un statut

✅
```markdown
- **Statut** — l'étape où en est une facture. Il s'affiche en badge dans la liste des factures.
  - **Brouillon** — la facture est en préparation ; le client ne la voit pas.
  - **Envoyée** — le client l'a reçue et ne l'a pas encore payée.
  - **En retard** — la date d'échéance est passée sans paiement complet.
  - **Payée** — le montant a été reçu en totalité.
```

### Un calcul : en mots, avec un exemple

❌ `` - **Taux de recouvrement** — `paid_amount / total_amount * 100`. ``

✅ `- **Taux de recouvrement** — la part de l'argent facturé qui a déjà été encaissée. Si 10 000 € ont été facturés ce mois-ci et 7 500 € payés, le taux est de 75 %.`

### Qui voit quoi

✅ `- **Remise** — une réduction appliquée au total d'une facture. Seuls les **Administrateurs** voient le champ et peuvent la modifier ; les **Comptables** voient le total déjà réduit.`

### Ce qui n'entre pas

- « Date de dernière synchronisation » stockée mais jamais affichée → écartée.
- « Taux de conversion devis → facture » cité dans la vision comme métrique produit, absent de
  l'écran → écarté.
- « Facturation récurrente », ligne V2 non cochée du backlog → écartée.
- « Export comptable », story ouverte et code non commité → soumis à l'utilisateur (inclure, à
  venir, retirer).

### Les menus

✅
```markdown
## Menus et navigation

- **Ventes** — tout ce qui concerne les clients et ce qu'on leur facture. Visible par tous les rôles.
  - **Clients** — la liste des clients, avec leur solde.
  - **Factures** — la liste des factures, filtrable par statut.
- **Réglages** — visible par les **Administrateurs** seulement.
  - **Utilisateurs** — inviter une personne et choisir son rôle.
```
