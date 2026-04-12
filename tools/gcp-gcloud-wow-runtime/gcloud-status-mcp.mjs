/**
 * Read-only Google Cloud CLI status MCP (RS Gravity, Apr 2026+).
 * No arbitrary shell — only fixed `gcloud` argument allowlists.
 */
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import { z } from 'zod';

const execFileAsync = promisify(execFile);

/** @param {string[]} args */
async function runGcloud(args) {
  const bin = process.platform === 'win32' ? 'gcloud.cmd' : 'gcloud';
  const { stdout } = await execFileAsync(bin, args, {
    maxBuffer: 12 * 1024 * 1024,
    windowsHide: true,
  });
  return stdout.trim();
}

const server = new McpServer({
  name: 'rs-gcloud-wow-status',
  version: '1.0.0',
});

server.registerTool(
  'gcloud_wow_version',
  {
    title: 'gcloud version (JSON)',
    description: 'Runs `gcloud version --format=json` for local CLI diagnostics.',
    inputSchema: z.object({}),
  },
  async () => {
    try {
      const out = await runGcloud(['version', '--format=json']);
      return { content: [{ type: 'text', text: out || '{}' }] };
    } catch (e) {
      const msg = e instanceof Error ? e.message : String(e);
      return {
        isError: true,
        content: [
          {
            type: 'text',
            text:
              `gcloud failed (is Google Cloud SDK installed?). ${msg}\n` +
              'Install: tools/gcp-gcloud-wow-runtime/install-gcloud-wow.ps1',
          },
        ],
      };
    }
  },
);

server.registerTool(
  'gcloud_wow_config_list',
  {
    title: 'gcloud config list (JSON)',
    description:
      'Runs `gcloud config list --format=json` (core, account, project). Read-only.',
    inputSchema: z.object({}),
  },
  async () => {
    try {
      const out = await runGcloud(['config', 'list', '--format=json']);
      return { content: [{ type: 'text', text: out || '{}' }] };
    } catch (e) {
      const msg = e instanceof Error ? e.message : String(e);
      return {
        isError: true,
        content: [{ type: 'text', text: `gcloud config list failed: ${msg}` }],
      };
    }
  },
);

const transport = new StdioServerTransport();
await server.connect(transport);
