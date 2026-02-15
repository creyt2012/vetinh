#!/bin/bash

# Configuration
REPO_PATH="/Users/creytdeveloper/Documents/vetinh"
SLEEP_INTERVAL=2

echo "Starting StarWeather Auto-Push Watcher..."
echo "Monitoring: $REPO_PATH"

fswatch -o "$REPO_PATH" -e "/.git" -e "/vendor" -e "/node_modules" -e "/storage" -e "/public/build" | while read num; do
    echo "Change detected. Syncing to GitHub..."
    cd "$REPO_PATH"
    
    # Check if there are changes to commit
    if [[ -n $(git status -s) ]]; then
        git add .
        git commit -m "Auto-sync: $(date '+%Y-%m-%d %H:%M:%S')"
        git push origin main
        echo "Successfully pushed changes."
    else
        echo "No relevant changes to commit."
    fi
done
