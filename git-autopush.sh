#!/bin/bash

# Configuration
REPO_PATH="/Users/creytdeveloper/Documents/vetinh"
LOG_FILE="$REPO_PATH/git-autopush.log"

echo "Starting StarWeather Ultra-Sync Watcher..."
echo "Monitoring: $REPO_PATH"

# fswatch configuration:
# -l 0.1: Extremely low latency (100ms)
# -o: Batch events by number (standard trigger)
# -r: Recursive
# -e: Exclusions to prevent infinite loops (especially .git and log files)

fswatch -o "$REPO_PATH" -l 0.1 -r \
    -e "/\.git" \
    -e "/vendor" \
    -e "/node_modules" \
    -e "/storage" \
    -e "/public/build" \
    -e "git-autopush\.log" | while read num; do
    
    cd "$REPO_PATH"
    
    # Get status of changes
    CHANGES=$(git status -s)
    
    if [[ -n "$CHANGES" ]]; then
        # Identify the first changed file for better logging
        FIRST_FILE=$(echo "$CHANGES" | head -n 1 | cut -c 4-)
        
        echo "[$(date '+%H:%M:%S')] Change detected in $FIRST_FILE. Syncing..." | tee -a "$LOG_FILE"
        
        git add .
        git commit -m "Auto-sync: $FIRST_FILE ($(date '+%H:%M:%S'))"
        git push origin main
        
        echo "[$(date '+%H:%M:%S')] Successfully pushed to GitHub." | tee -a "$LOG_FILE"
    fi
done
