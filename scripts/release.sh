#!/bin/bash
set -e

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Parse arguments
COMMAND=$1
VERSION=$2

# Handle command variations
if [ "$COMMAND" == "delete" ] || [ "$COMMAND" == "remove" ] || [ "$COMMAND" == "rollback" ]; then
    ACTION="delete"
    if [ -z "$VERSION" ]; then
        echo "Usage: ./scripts/release.sh delete <version>"
        echo "Example: ./scripts/release.sh delete 2.5.0"
        exit 1
    fi
elif [ -n "$COMMAND" ] && [ -z "$VERSION" ]; then
    # Single argument - assume it's version for creation
    ACTION="create"
    VERSION=$COMMAND
else
    echo "Usage:"
    echo "  Create release:  ./scripts/release.sh <version>"
    echo "  Delete release:  ./scripts/release.sh delete <version>"
    echo ""
    echo "Examples:"
    echo "  ./scripts/release.sh 2.5.0"
    echo "  ./scripts/release.sh delete 2.5.0"
    exit 1
fi

TAG="v${VERSION}"

# ========================================
# DELETE RELEASE
# ========================================
if [ "$ACTION" == "delete" ]; then
    echo "========================================="
    echo "🗑️  Deleting release ${TAG}..."
    echo "========================================="
    echo ""
    
    # Confirm deletion
    echo "⚠️  This will delete:"
    echo "   - Local tag: ${TAG}"
    echo "   - Remote tag: ${TAG}"
    echo "   - You'll need to manually delete the GitHub Release"
    echo ""
    read -p "Are you sure? (yes/no): " CONFIRM
    
    if [ "$CONFIRM" != "yes" ]; then
        echo "❌ Deletion cancelled"
        exit 1
    fi
    
    echo ""
    echo "🏷️  Deleting local tag..."
    git tag -d "${TAG}" 2>/dev/null && echo "✅ Local tag deleted" || echo "  (tag doesn't exist locally)"
    
    echo ""
    echo "🏷️  Deleting remote tag..."
    git push origin ":refs/tags/${TAG}" 2>/dev/null && echo "✅ Remote tag deleted" || echo "  (tag doesn't exist on remote)"
    
    echo ""
    echo "========================================="
    echo "✅ Tag ${TAG} deleted successfully!"
    echo "========================================="
    echo ""
    echo "📦 Don't forget to manually delete the GitHub Release at:"
    echo "   https://github.com/tommyknocker/chain/releases"
    echo ""
    exit 0
fi

# ========================================
# CREATE RELEASE
# ========================================

echo "========================================="
echo "📋 Preparing Chain Library release ${TAG}..."
echo "========================================="
echo ""

# Check for uncommitted changes
echo "🔍 Checking git status..."
if [ -n "$(git status --porcelain)" ]; then
    echo "❌ You have uncommitted changes. Commit or stash them first."
    exit 1
fi
echo "✅ No uncommitted changes"

# Check current branch is master
echo ""
echo "🔍 Checking current branch..."
BRANCH=$(git rev-parse --abbrev-ref HEAD)
if [ "$BRANCH" != "master" ]; then
    echo "❌ You must be on master branch. Current: $BRANCH"
    exit 1
fi
echo "✅ On master branch"
echo ""

# Check for Russian characters in PHP files
echo "🔍 Checking for Russian characters in PHP files..."
RUSSIAN_FILES=$(find src tests examples -name "*.php" -type f -exec grep -l '[А-Яа-яЁё]' {} \; 2>/dev/null || true)

if [ -n "$RUSSIAN_FILES" ]; then
    echo "❌ Found Russian characters in PHP files:"
    echo "$RUSSIAN_FILES" | while read file; do
        echo "   - $file"
        grep -n '[А-Яа-яЁё]' "$file" | head -3 | sed 's/^/     Line /'
    done
    echo ""
    echo "Please remove all Russian text from code and comments."
    echo "Use English for all code, comments, and documentation."
    exit 1
fi
echo "✅ No Russian characters found in PHP files"
echo ""

# Run quality checks
echo "🔍 Running quality checks..."
composer quality

if [ $? -ne 0 ]; then
    echo "❌ Quality checks failed. Fix them before releasing."
    exit 1
fi
echo "✅ All quality checks passed!"

# Verify all examples work
echo ""
echo "📝 Verifying all examples..."
composer examples

if [ $? -ne 0 ]; then
    echo "❌ Some examples failed. Fix them before releasing."
    exit 1
fi
echo "✅ All examples work correctly!"

# Create tag
echo ""
echo "🏷️  Creating tag ${TAG}..."
if git rev-parse -q --verify "refs/tags/${TAG}" >/dev/null; then
    echo "❌ Tag ${TAG} already exists locally. Refusing to overwrite a release."
    exit 1
fi

if git ls-remote --exit-code --tags origin "refs/tags/${TAG}" >/dev/null 2>&1; then
    echo "❌ Tag ${TAG} already exists on origin. Refusing to overwrite a release."
    exit 1
fi

git tag -a "${TAG}" -m "Release ${TAG}"

# Push to remote
echo ""
echo "📤 Pushing to origin..."
git push origin master
git push origin "${TAG}"

echo ""
echo "========================================="
echo "✅ Release ${TAG} created successfully!"
echo "========================================="
echo ""
echo "📦 Next steps:"
echo "   1. Go to: https://github.com/tommyknocker/chain/releases/new?tag=${TAG}"
echo "   2. Set title: 'v${VERSION}'"
echo "   3. Paste the release notes from CHANGELOG.md"
echo "   4. Publish release"
echo ""
