#!/bin/bash

# Configuration
REPO_PATH="/Users/creytdeveloper/Documents/vetinh"
LOG_FILE="$REPO_PATH/git-autopush.log"

echo "Starting StarWeather Ultra-Sync Watcher (Fixed Exclusions)..."
echo "Monitoring: $REPO_PATH"

# fswatch configuration:
# -l 0.1: Extremely low latency (100ms)
# -o: Batch events
# -r: Recursive
# -e: Exclusions (Regex based). We must ensure the log file doesn't trigger a sync.

fswatch -o "$REPO_PATH" -l 0.1 -r \
    -e "/\.git" \
    -e "/vendor" \
    -e "/node_modules" \
    -e "/storage" \
    -e "/public/build" \
    -e "git-autopush\.log$" \
    -e "git-autopush\.out$" | while read num; do
    
    cd "$REPO_PATH"
    
    # Get status of changes, excluding the log files themselves from the commit check
    CHANGES=$(git status -s | grep -v "git-autopush.log" | grep -v "git-autopush.out")
    
    if [[ -n "$CHANGES" ]]; then
        # Identify the first changed file for better logging
        FIRST_FILE=$(echo "$CHANGES" | head -n 1 | cut -c 4-)
        
        TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
        echo "[$TIMESTAMP] Change detected in $FIRST_FILE. Syncing..." >> "$LOG_FILE"
        
        git add .
        # We explicitly don't care if the log is added, but we want the commit message to be clean
        git commit -m "Auto-sync: $FIRST_FILE ($TIMESTAMP)"
        git push origin main
        
        echo "[$TIMESTAMP] Successfully pushed to GitHub." >> "$LOG_FILE"
    fi
done
