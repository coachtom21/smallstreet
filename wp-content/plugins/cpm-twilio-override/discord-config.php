<?php
/**
 * Discord Bot Configuration
 * 
 * IMPORTANT: Replace these placeholder values with your actual Discord bot credentials
 * 
 * How to get these values:
 * 1. Create a Discord application at https://discord.com/developers/applications
 * 2. Create a bot for your application
 * 3. Get the bot token from the Bot section
 * 4. Get the server (guild) ID by enabling Developer Mode in Discord and right-clicking your server
 */

// Discord Bot Token (from Discord Developer Portal)
define('DISCORD_BOT_TOKEN', 'YOUR_DISCORD_BOT_TOKEN_HERE');

// Discord Server (Guild) ID
define('DISCORD_SERVER_ID', 'YOUR_DISCORD_SERVER_ID_HERE');

// Discord API Base URL (usually don't change this)
define('DISCORD_API_BASE', 'https://discord.com/api/v10');

// Discord Server Invite Link (for users to join)
define('DISCORD_INVITE_LINK', 'https://discord.gg/tY6mxRft');

// Bot Permissions Required:
// - View Channels
// - Read Message History
// - Use Slash Commands
// - Send Messages (optional, for notifications)

// Server Permissions Required:
// - Manage Server (to view member list)
// - View Channels
// - Read Message History

/**
 * Example Configuration (replace with your actual values):
 * 
 * define('DISCORD_BOT_TOKEN', 'MTIzNDU2Nzg5MDEyMzQ1Njc4OTAxMjM0NTY3ODkwMTIzNDU2Nzg5MA.GhIjKl.MnOpQrStUvWxYzAbCdEfGhIjKlMnOpQrStUvWx');
 * define('DISCORD_SERVER_ID', '1234567890123456789');
 * define('DISCORD_INVITE_LINK', 'https://discord.gg/yourinvitelink');
 */

/**
 * Security Notes:
 * - Never share your bot token publicly
 * - Keep this file secure and restrict access
 * - Consider using environment variables for production
 * - Regularly rotate your bot token if compromised
 */
