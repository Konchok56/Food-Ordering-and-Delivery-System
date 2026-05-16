# -*- coding: utf-8 -*-
# Save this file as UTF-8 with BOM

$replacements = @(
    # Status / UI
    @{ old = "✅";    new = '<i class="fa-solid fa-circle-check" style="color:#22c55e"></i>' }
    @{ old = "❌";    new = '<i class="fa-solid fa-circle-xmark" style="color:#ef4444"></i>' }
    @{ old = "⚠️";   new = '<i class="fa-solid fa-triangle-exclamation" style="color:#f59e0b"></i>' }
    @{ old = "⚠";    new = '<i class="fa-solid fa-triangle-exclamation" style="color:#f59e0b"></i>' }
    @{ old = "ℹ️";   new = '<i class="fa-solid fa-circle-info" style="color:#3b82f6"></i>' }
    @{ old = "ℹ";    new = '<i class="fa-solid fa-circle-info" style="color:#3b82f6"></i>' }
    @{ old = "⏳";   new = '<i class="fa-solid fa-hourglass-half" style="color:#f59e0b"></i>' }
    @{ old = "⏰";   new = '<i class="fa-regular fa-clock"></i>' }
    @{ old = "🎉";   new = '<i class="fa-solid fa-champagne-glasses" style="color:#22c55e"></i>' }
    @{ old = "🔔";   new = '<i class="fa-solid fa-bell"></i>' }

    # Navigation / User
    @{ old = "👤";   new = '<i class="fa-solid fa-user"></i>' }
    @{ old = "👨‍💼"; new = '<i class="fa-solid fa-user-tie"></i>' }
    @{ old = "👨‍🍳"; new = '<i class="fa-solid fa-kitchen-set"></i>' }
    @{ old = "🚪";   new = '<i class="fa-solid fa-right-from-bracket"></i>' }
    @{ old = "📊";   new = '<i class="fa-solid fa-chart-bar"></i>' }
    @{ old = "📦";   new = '<i class="fa-solid fa-box"></i>' }
    @{ old = "📱";   new = '<i class="fa-solid fa-mobile-screen"></i>' }
    @{ old = "🏠";   new = '<i class="fa-solid fa-house"></i>' }

    # Food / Delivery
    @{ old = "🍔";   new = '<i class="fa-solid fa-burger"></i>' }
    @{ old = "🍕";   new = '<i class="fa-solid fa-pizza-slice"></i>' }
    @{ old = "🍣";   new = '<i class="fa-solid fa-fish"></i>' }
    @{ old = "🍛";   new = '<i class="fa-solid fa-bowl-food"></i>' }
    @{ old = "🍜";   new = '<i class="fa-solid fa-bowl-food"></i>' }
    @{ old = "🥘";   new = '<i class="fa-solid fa-bowl-food"></i>' }
    @{ old = "🥡";   new = '<i class="fa-solid fa-bowl-rice"></i>' }
    @{ old = "🌮";   new = '<i class="fa-solid fa-bowl-food"></i>' }
    @{ old = "🥟";   new = '<i class="fa-solid fa-bowl-food"></i>' }
    @{ old = "🥩";   new = '<i class="fa-solid fa-drumstick-bite"></i>' }
    @{ old = "☕";   new = '<i class="fa-solid fa-mug-hot"></i>' }
    @{ old = "🧁";   new = '<i class="fa-solid fa-cake-candles"></i>' }
    @{ old = "🦐";   new = '<i class="fa-solid fa-fish"></i>' }
    @{ old = "🍴";   new = '<i class="fa-solid fa-utensils"></i>' }
    @{ old = "🍽️";  new = '<i class="fa-solid fa-utensils"></i>' }
    @{ old = "🍽";   new = '<i class="fa-solid fa-utensils"></i>' }
    @{ old = "🥗";   new = '<i class="fa-solid fa-leaf"></i>' }
    @{ old = "🛵";   new = '<i class="fa-solid fa-motorcycle"></i>' }
    @{ old = "🚚";   new = '<i class="fa-solid fa-truck"></i>' }
    @{ old = "🚲";   new = '<i class="fa-solid fa-bicycle"></i>' }
    @{ old = "🏍️";  new = '<i class="fa-solid fa-motorcycle"></i>' }
    @{ old = "🏍";   new = '<i class="fa-solid fa-motorcycle"></i>' }

    # General
    @{ old = "⭐";   new = '<i class="fa-solid fa-star" style="color:#f59e0b"></i>' }
    @{ old = "✨";   new = '<i class="fa-solid fa-wand-magic-sparkles" style="color:#f59e0b"></i>' }
    @{ old = "🔥";   new = '<i class="fa-solid fa-fire" style="color:#ef4444"></i>' }
    @{ old = "❤️";   new = '<i class="fa-solid fa-heart" style="color:#ef4444"></i>' }
    @{ old = "❤";    new = '<i class="fa-solid fa-heart" style="color:#ef4444"></i>' }
    @{ old = "🤍";   new = '<i class="fa-regular fa-heart"></i>' }
    @{ old = "🛒";   new = '<i class="fa-solid fa-cart-shopping"></i>' }
    @{ old = "📍";   new = '<i class="fa-solid fa-location-dot"></i>' }
    @{ old = "💰";   new = '<i class="fa-solid fa-coins"></i>' }
    @{ old = "🗑️";  new = '<i class="fa-solid fa-trash"></i>' }
    @{ old = "🗑";   new = '<i class="fa-solid fa-trash"></i>' }
    @{ old = "🔐";   new = '<i class="fa-solid fa-lock"></i>' }
    @{ old = "🔑";   new = '<i class="fa-solid fa-key"></i>' }
    @{ old = "📧";   new = '<i class="fa-solid fa-envelope"></i>' }
    @{ old = "📞";   new = '<i class="fa-solid fa-phone"></i>' }
    @{ old = "🚀";   new = '<i class="fa-solid fa-rocket"></i>' }
    @{ old = "▶️";   new = '<i class="fa-brands fa-youtube"></i>' }
    @{ old = "☰";    new = '<i class="fa-solid fa-bars"></i>' }

    # HTML entity versions
    @{ old = "&#9728;&#65039;";    new = '<i class="fa-solid fa-sun"></i>' }
    @{ old = "&#127769;";          new = '<i class="fa-solid fa-moon"></i>' }
    @{ old = "&#x1F37D;&#xFE0F;"; new = '<i class="fa-solid fa-utensils"></i>' }
    @{ old = "&#x1F37D;";         new = '<i class="fa-solid fa-utensils"></i>' }
    @{ old = "&#x1F550;";         new = '<i class="fa-regular fa-clock"></i>' }
    @{ old = "&#x1F50D;";         new = '<i class="fa-solid fa-magnifying-glass"></i>' }
    @{ old = "&#x2764;&#xFE0F;";  new = '<i class="fa-solid fa-heart" style="color:#ef4444"></i>' }
    @{ old = "&#x1F90D;";         new = '<i class="fa-regular fa-heart"></i>' }
    @{ old = "&#9733;";            new = '<i class="fa-solid fa-star" style="color:#f59e0b"></i>' }
)

$files = Get-ChildItem -Recurse -Include "*.php" -Path "." |
         Where-Object { $_.FullName -notmatch "\\vendor\\" -and $_.FullName -notmatch "\\scratch\\" }

$totalChanged = 0
foreach ($file in $files) {
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    $changed = $false
    foreach ($r in $replacements) {
        if ($content.Contains($r.old)) {
            $content = $content.Replace($r.old, $r.new)
            $changed = $true
        }
    }
    if ($changed) {
        [System.IO.File]::WriteAllText($file.FullName, $content, [System.Text.Encoding]::UTF8)
        Write-Host "Updated: $($file.Name)"
        $totalChanged++
    }
}
Write-Host "`n$totalChanged files updated. All emojis replaced with Font Awesome icons!"
