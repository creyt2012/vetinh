#!/bin/bash

# Configuration
REPO_PATH="/Users/creytdeveloper/Documents/vetinh"
LOG_FILE="$REPO_PATH/git-autopush.log"

echo "Starting StarWeather Ultra-Sync Watcher (V3 - Hardened)..."
echo "Monitoring: $REPO_PATH"

# fswatch with strict exclusions to prevent self-triggering
fswatch -o "$REPO_PATH" -l 0.1 -r \
    -e "/\.git" \
    -e "/vendor" \
    -e "/node_modules" \
    -e "/storage" \
    -e "/public/build" \
    -e "git-autopush\.log" \
    -e "git-autopush\.out" | while read num; do
    
    cd "$REPO_PATH"
    
    # Identify if there are any changes besides the log files
    # (Though adding them to .gitignore already handles this, this is dual-safety)
    CHANGES=$(git status -s | grep -v "git-autopush.log" | grep -v "git-autopush.out")
    
    if [[ -n "$CHANGES" ]]; then
        FIRST_FILE=$(echo "$CHANGES" | head -n 1 | cut -c 4-)
        TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
        
        echo "[$TIMESTAMP] Change detected in $FIRST_FILE. Syncing..." >> "$LOG_FILE"
        
        # Ensure identity is local for this repo
        git config user.name "creyt2012"
        git config user.email "mortarcloud@gmail.com"
        
        git add .
        git commit -m "Auto-sync: $FIRST_FILE ($TIMESTAMP)"
        git push origin main
        
        echo "[$TIMESTAMP] Successfully pushed to GitHub." >> "$LOG_FILE"
    fi
done
