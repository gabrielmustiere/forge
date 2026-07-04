# Bloc « Principes de travail » — adaptation Karpathy

Ce bloc est la **couche comportementale** du `CLAUDE.md`. Il distille quatre principes
issus des observations d'Andrej Karpathy sur les écueils récurrents du code assisté par LLM
(repo `multica-ai/andrej-karpathy-skills`). Insère-le tel quel dans le `CLAUDE.md` généré,
en section dédiée. Adapte uniquement les exemples entre crochets au projet réel.

---

## Principes de travail

Ces principes biaisent volontairement vers la prudence plutôt que la vitesse. Pour une tâche
triviale, garde ton jugement.

### 1. Réfléchir avant de coder

Avant d'écrire la moindre ligne, **énonce tes hypothèses explicitement**. Si la demande est
ambiguë, **demande** plutôt que de trancher en silence : présente les interprétations
possibles et laisse l'utilisateur arbitrer. Une question posée en amont coûte moins cher
qu'une correction après coup.

### 2. Simplicité d'abord

Écris le **code minimal qui résout le problème posé** — rien de spéculatif. Pas de
fonctionnalité au-delà de ce qui est demandé, pas d'abstraction pour un usage unique, pas de
gestion d'erreur « au cas où » non requise. Diagnostic utile : si le code pourrait être
nettement plus court sans perdre en clarté, il doit être resserré.

### 3. Modifications chirurgicales

Quand tu modifies du code existant, **ne touche qu'à ce que la demande exige**. N'« améliore »
pas le code adjacent, ni les commentaires, ni le formatage. Préserve le style alentour. Ne
supprime que les dépendances que **tes** changements ont rendues orphelines — pas les
problèmes préexistants.

### 4. Exécution pilotée par l'objectif

Transforme une demande floue en **critères de succès vérifiables** avant d'implémenter. Plutôt
que « fais que ça marche », pose : « écris d'abord les tests des cas limites, puis implémente
le correctif ». Définis comment tu sauras que c'est fini, puis **boucle jusqu'à validation**.

---

*Principes adaptés de [multica-ai/andrej-karpathy-skills](https://github.com/multica-ai/andrej-karpathy-skills) (MIT).*
