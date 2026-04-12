/**
 * Telegram Bot API — minimal MCP for operator messaging (RS Gravity, Apr 2026+).
 * Secrets: TELEGRAM_BOT_TOKEN, optional TELEGRAM_CHAT_ID (default for send).
 */
import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import { z } from 'zod';

const token = (process.env.TELEGRAM_BOT_TOKEN || '').trim();
const defaultChat = (process.env.TELEGRAM_CHAT_ID || '').trim();

async function tgApi(method, body) {
  const url = `https://api.telegram.org/bot${encodeURIComponent(token)}/${method}`;
  const init = body
    ? {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      }
    : { method: 'GET' };
  const res = await fetch(url, init);
  const json = await res.json().catch(() => ({}));
  if (!res.ok || json.ok === false) {
    const desc = json.description || res.statusText || 'Telegram API error';
    throw new Error(desc);
  }
  return json;
}

const server = new McpServer({
  name: 'rs-telegram-operator',
  version: '1.0.0',
});

server.registerTool(
  'telegram_get_me',
  {
    title: 'Telegram getMe',
    description: 'Verify bot token (Bot API getMe).',
    inputSchema: z.object({}),
  },
  async () => {
    const json = await tgApi('getMe');
    return {
      content: [{ type: 'text', text: JSON.stringify(json.result, null, 2) }],
    };
  },
);

server.registerTool(
  'telegram_send_message',
  {
    title: 'Telegram sendMessage',
    description:
      'Send a text message. Uses TELEGRAM_CHAT_ID from mcp.env when chat_id is omitted.',
    inputSchema: {
      text: z.string().min(1).describe('Message text'),
      chat_id: z.string().optional().describe('Recipient chat id (override)'),
      parse_mode: z
        .enum(['HTML', 'Markdown', 'MarkdownV2'])
        .optional()
        .describe('Optional parse mode'),
      disable_web_page_preview: z.boolean().optional(),
    },
  },
  async ({ text, chat_id, parse_mode, disable_web_page_preview }) => {
    const cid = (chat_id || defaultChat).trim();
    if (!cid) {
      throw new Error(
        'Missing chat_id and TELEGRAM_CHAT_ID is not set in environment.',
      );
    }
    const payload = {
      chat_id: cid,
      text,
      ...(parse_mode ? { parse_mode } : {}),
      ...(typeof disable_web_page_preview === 'boolean'
        ? { disable_web_page_preview }
        : {}),
    };
    const json = await tgApi('sendMessage', payload);
    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify(
            { message_id: json.result?.message_id, chat: json.result?.chat },
            null,
            2,
          ),
        },
      ],
    };
  },
);

if (!token) {
  console.error(
    'telegram MCP: missing TELEGRAM_BOT_TOKEN. Set in .cursor/mcp.env — see tools/telegram-mcp-runtime/README.md',
  );
  process.exit(1);
}

const transport = new StdioServerTransport();
await server.connect(transport);
