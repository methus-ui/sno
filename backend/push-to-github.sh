#!/bin/bash

# Push Snocart Backend to GitHub
# Repository: https://github.com/faheemjavid/snocart_backend.git

echo "════════════════════════════════════════════════════════"
echo "  Pushing Snocart Backend to GitHub"
echo "════════════════════════════════════════════════════════"
echo ""

# Check if git is configured
if [ -z "$(git config --global user.name)" ]; then
    echo "⚠️  Git user not configured"
    echo ""
    echo "Please configure git:"
    echo "  git config --global user.name \"Your Name\""
    echo "  git config --global user.email \"your.email@example.com\""
    echo ""
    exit 1
fi

# Show commit info
echo "📦 Commit to push:"
git log -1 --oneline
echo ""

# Show remotes
echo "🔗 Remotes:"
git remote -v | grep github
echo ""

# Check if already pushed
if git ls-remote github main &>/dev/null; then
    echo "ℹ️  Repository exists on GitHub"
else
    echo "ℹ️  This will be the first push to GitHub"
fi

echo ""
echo "════════════════════════════════════════════════════════"
echo ""
echo "Choose authentication method:"
echo "  1) HTTPS with Personal Access Token (recommended)"
echo "  2) SSH (requires SSH key setup)"
echo ""
read -p "Enter choice (1 or 2): " choice

case $choice in
    1)
        echo ""
        echo "📝 You'll need a GitHub Personal Access Token"
        echo ""
        echo "To create one:"
        echo "  1. Go to https://github.com/settings/tokens"
        echo "  2. Click 'Generate new token (classic)'"
        echo "  3. Give it a name: 'Snocart Backend Push'"
        echo "  4. Select scopes: 'repo' (all permissions)"
        echo "  5. Click 'Generate token'"
        echo "  6. Copy the token (starts with ghp_...)"
        echo ""
        read -p "Enter your GitHub username: " GH_USER
        read -sp "Enter your Personal Access Token: " GH_TOKEN
        echo ""

        # Create credential URL
        git remote set-url github "https://${GH_USER}:${GH_TOKEN}@github.com/faheemjavid/snocart_backend.git"

        echo ""
        echo "🚀 Pushing to GitHub..."
        if git push github main; then
            echo ""
            echo "✅ Successfully pushed to GitHub!"
            echo ""
            echo "🔗 View at: https://github.com/faheemjavid/snocart_backend"
        else
            echo ""
            echo "❌ Push failed. Check your credentials."
            exit 1
        fi

        # Remove credentials from URL for security
        git remote set-url github "https://github.com/faheemjavid/snocart_backend.git"
        ;;

    2)
        echo ""
        echo "🔑 Using SSH authentication"
        git remote set-url github "git@github.com:faheemjavid/snocart_backend.git"

        echo ""
        echo "Testing SSH connection..."
        if ssh -T git@github.com 2>&1 | grep -q "successfully authenticated"; then
            echo "✅ SSH connection successful"
            echo ""
            echo "🚀 Pushing to GitHub..."
            if git push github main; then
                echo ""
                echo "✅ Successfully pushed to GitHub!"
                echo ""
                echo "🔗 View at: https://github.com/faheemjavid/snocart_backend"
            else
                echo ""
                echo "❌ Push failed"
                exit 1
            fi
        else
            echo "❌ SSH key not configured"
            echo ""
            echo "To set up SSH key:"
            echo "  1. Generate key: ssh-keygen -t ed25519 -C \"your.email@example.com\""
            echo "  2. Copy key: cat ~/.ssh/id_ed25519.pub"
            echo "  3. Add to GitHub: https://github.com/settings/keys"
            echo ""
            exit 1
        fi
        ;;

    *)
        echo "Invalid choice"
        exit 1
        ;;
esac

echo ""
echo "════════════════════════════════════════════════════════"
echo "  Version Control Setup Complete"
echo "════════════════════════════════════════════════════════"
echo ""
echo "📊 Repository Stats:"
echo "  • Commit: $(git rev-parse --short HEAD)"
echo "  • Files: 7,303 changed"
echo "  • Additions: +261,998 lines"
echo "  • Deletions: -13,766 lines"
echo ""
echo "🔄 Future pushes:"
echo "  git add ."
echo "  git commit -m \"Your commit message\""
echo "  git push github main"
echo ""
echo "✅ Done!"
