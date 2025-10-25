#!/bin/bash
set -e

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo "========================================="
echo "Testing Chain Library Examples"
echo "========================================="
echo ""

# Counter
TOTAL=0
PASSED=0
FAILED=0

# Create build directory if it doesn't exist
mkdir -p build

# Find all example PHP files
for file in examples/*.php; do
    if [ -f "$file" ]; then
        TOTAL=$((TOTAL + 1))
        filename=$(basename "$file")
        category=$(basename $(dirname "$file"))

        echo -n "[$category] $filename ... "

        # Run with timeout and capture output
        if timeout 10 php "$file" > "build/example_${filename}.log" 2>&1; then
            echo -e "${GREEN}✓ PASSED${NC}"
            PASSED=$((PASSED + 1))
        else
            echo -e "${RED}✗ FAILED${NC}"
            FAILED=$((FAILED + 1))

            # Show error for failed tests
            if [ "$1" == "--verbose" ] || [ "$1" == "-v" ]; then
                echo -e "${YELLOW}Error output:${NC}"
                cat "build/example_${filename}.log" | tail -10
                echo ""
            fi
        fi
    fi
done

echo ""
echo "========================================="
echo "Results: $PASSED/$TOTAL passed"

if [ $FAILED -gt 0 ]; then
    echo -e "${RED}$FAILED failed${NC}"
    echo ""
    echo "Run with --verbose to see error details"
    echo "Logs saved in build/example_*.log"
    exit 1
else
    echo -e "${GREEN}All examples passed!${NC}"
    echo ""
    echo -e "${BLUE}Available examples:${NC}"
    for file in examples/*.php; do
        if [ -f "$file" ]; then
            filename=$(basename "$file")
            echo "  - php examples/$filename"
        fi
    done
    exit 0
fi