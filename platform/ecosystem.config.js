/**
 * PM2 Configuration — Hostinger Cloud Hosting
 *
 * Avvio: pm2 start ecosystem.config.js --env production
 * Riavvio: pm2 reload metaborghi
 * Log: pm2 logs metaborghi
 */
module.exports = {
  apps: [
    {
      name: 'metaborghi',
      script: 'node_modules/.bin/next',
      args: 'start -p 3000',
      cwd: '/var/www/metaborghi/platform',
      instances: 1,
      exec_mode: 'fork',
      env_production: {
        NODE_ENV: 'production',
        PORT: 3000,
      },
      max_memory_restart: '512M',
      error_file: '/var/log/pm2/metaborghi-error.log',
      out_file: '/var/log/pm2/metaborghi-out.log',
      merge_logs: true,
      log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
    },
  ],
}
