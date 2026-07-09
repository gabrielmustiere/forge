<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    // `private/` héberge les clones locaux des repos de projets (story 008) : ce sont des
    // artefacts de travail, jamais à réécrire par notre style — sinon `make quality` en local
    // corromprait les dépôts clonés (leur propre style n'est pas le nôtre).
    ->exclude('private')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'concat_space' => ['spacing' => 'one'],
    ])
    ->setFinder($finder)
;
