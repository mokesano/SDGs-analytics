// release.config.js
module.exports = {
  branches: [
    'master',  // Hanya release dari branch master
    { name: 'production', channel: 'stable', prerelease: false }  // Opsional: jika ingin release dari production branch
  ],
  plugins: [
    // 1. Analisis commit untuk menentukan versi (Conventional Commits)
    [
      '@semantic-release/commit-analyzer',
      {
        preset: 'conventionalcommits',
        releaseRules: [
          { type: 'docs', scope: 'README', release: 'patch' },
          { type: 'refactor', release: 'patch' },
          { type: 'style', release: 'patch' },
          { type: 'perf', release: 'minor' },
          { breaking: true, release: 'major' },
        ],
      },
    ],
    
    // 2. Generate changelog dari commit messages
    [
      '@semantic-release/release-notes-generator',
      {
        preset: 'conventionalcommits',
        presetConfig: {
          types: [
            { type: 'feat', section: '🚀 Features' },
            { type: 'fix', section: '🐛 Bug Fixes' },
            { type: 'docs', section: '📚 Documentation' },
            { type: 'style', section: '🎨 Styling' },
            { type: 'refactor', section: '♻️ Code Refactoring' },
            { type: 'perf', section: '⚡ Performance' },
            { type: 'test', section: '🧪 Tests' },
            { type: 'chore', section: '🔧 Chores', hidden: false },
          ],
        },
      },
    ],
    
    // 3. Update CHANGELOG.md
    [
      '@semantic-release/changelog',
      {
        changelogFile: 'CHANGELOG.md',
      },
    ],
    
    // 4. Update composer.json dengan versi baru (PENTING untuk PHP!)
    [
      '@semantic-release/git',
      {
        assets: ['composer.json', 'CHANGELOG.md'],
        message: 'chore(release): ${nextRelease.version} [skip ci]\n\n${nextRelease.notes}',
      },
    ],
    
    // 5. Buat GitHub Release & Git Tag
    [
      '@semantic-release/github',
      {
        assets: [
          { path: 'dist/*.zip', label: 'Distribution Package' },  // Opsional: upload artifact
        ],
        successComment: false,
        failComment: false,
      },
    ],
  ],
};