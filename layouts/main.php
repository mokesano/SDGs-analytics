<!DOCTYPE html>
<html lang="id" class="<?php echo $darkModeClass ?? ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Wizdam Sikola'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription ?? 'SDGs Classification & Research Analytics Platform'); ?>">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (built) -->
    <link rel="stylesheet" href="/dist/assets/styles-CEd_xe0g.css">
</head>
<body class="bg-bg text-text transition-colors duration-300">
    <!-- Navbar -->
    <nav class="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-8">
                    <a href="/" class="text-2xl font-heading font-bold gradient-text">
                        Wizdam Sikola
                    </a>
                    <div class="hidden md:flex space-x-6">
                        <a href="/archived" class="text-text-muted hover:text-primary transition-colors">Archive</a>
                        <a href="/analytics" class="text-text-muted hover:text-primary transition-colors">Analytics</a>
                        <a href="/leaderboard" class="text-text-muted hover:text-primary transition-colors">Leaderboard</a>
                        <a href="/about" class="text-text-muted hover:text-primary transition-colors">About</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button id="dark-mode-toggle" class="p-2 rounded-lg hover:bg-bg-muted transition-colors" aria-label="Toggle dark mode">
                        <svg class="w-5 h-5 hidden dark:block" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"/></svg>
                        <svg class="w-5 h-5 block dark:hidden" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/></svg>
                    </button>
                    <?php if (isset($isLoggedIn) && $isLoggedIn): ?>
                        <a href="/dashboard" class="btn btn-primary">Dashboard</a>
                        <a href="/logout" class="btn btn-outline">Logout</a>
                    <?php else: ?>
                        <a href="/login" class="btn btn-outline">Login</a>
                        <a href="/register" class="btn btn-primary">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="min-h-screen">
        <?php echo $content ?? ''; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-bg-soft dark:bg-bg-darkMuted border-t border-border dark:border-border-dark mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="col-span-1 md:col-span-2">
                    <h3 class="text-xl font-heading font-bold gradient-text mb-4">Wizdam Sikola</h3>
                    <p class="text-text-muted max-w-md">
                        SDGs Classification & Research Analytics Platform untuk meningkatkan dampak penelitian terhadap tujuan pembangunan berkelanjutan.
                    </p>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="/archived" class="text-text-muted hover:text-primary">Archive</a></li>
                        <li><a href="/analytics" class="text-text-muted hover:text-primary">Analytics</a></li>
                        <li><a href="/leaderboard" class="text-text-muted hover:text-primary">Leaderboard</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Contact</h4>
                    <ul class="space-y-2 text-text-muted">
                        <li>PT. Sangia Research Media</li>
                        <li>wizdam@sangia.org</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-border dark:border-border-dark mt-8 pt-8 text-center text-text-muted">
                <p>&copy; <?php echo date('Y'); ?> Wizdam Sikola. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script type="module" src="/dist/assets/main.js"></script>
</body>
</html>
