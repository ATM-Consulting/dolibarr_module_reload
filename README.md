

### Options de lancement du script :
unactivate **0**/1: Si 1 alors les modules sont désactivés avant reload (si le module est une dépendance alors celà peut induire la désactivation involontaire d'autres modules)
dependency 0/**1** : Si 1 (default) alors les dépendances des modules sont désactivées/activées fonction de la configuration

### Pour utiliser le script :
1. Se connecter en administrateur sur l'instance
2. lancer le script https://your-dolibarr.ldt/custom/reloadcustom/script/reload.php?unactivate=1&dependency=0

