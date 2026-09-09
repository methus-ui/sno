module.exports = {
  apps: [{
    name: 'snocart-web',
    script: 'npm',
    args: 'start',
    cwd: '/var/www/html/new_public/new/snocart-web',
    instances: 1,
    autorestart: true,
    watch: false,
    max_memory_restart: '1G',
    env: {
      NODE_ENV: 'production',
      PORT: 3001
    }
  }]
};
