#!/bin/bash

# Configuration
REPO_PATH="/Users/creytdeveloper/Documents/vetinh"
LOG_FILE="$REPO_PATH/git-autopush.log"

# Global Identity Enforcement (Local to Repo)
cd "$REPO_PATH"
git config user.name "creyt2012"
git config user.email "mortarcloud@gmail.com"

echo "Starting StarWeather Bien Update Watcher (V4 - Hyper-Granular)..."
echo "Monitoring: $REPO_PATH (Commit-on-change: Enabled)"

# fswatch with ultra-low latency (0.1s)
fswatch -o "$REPO_PATH" -l 0.1 -r \
    -e "/\.git" \
    -e "/vendor" \
    -e "/node_modules" \
    -e "/storage" \
    -e "/public/build" \
    -e "\.log$" \
    -e "\.out$" | while read num; do
    
    cd "$REPO_PATH"
    
    # Check for ANY change including untracked files
    if [[ -n $(git status -s) ]]; then
        FIRST_FILE=$(git status -s | head -n 1 | cut -c 4- | xargs)
        TIMESTAMP=$(date '+%H:%M:%S.%3N') # Micro-precision
        
        echo "[$TIMESTAMP] Hyper-Sync triggered by $FIRST_FILE" >> "$LOG_FILE"
        
        git add -A
        git commit -m "Bien Update: $FIRST_FILE ($TIMESTAMP)"
        git push origin main >> "$LOG_FILE" 2>&1
        
        echo "[$TIMESTAMP] Pushed to GitHub. Contribution secured." >> "$LOG_FILE"
    fi
done
