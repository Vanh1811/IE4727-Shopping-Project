import type { APIRequestContext } from '@playwright/test';
import { readE2EConfig } from '../config/env';

export interface MailpitSummary {
  ID: string;
  Subject?: string;
  Created?: string;
  To?: Array<{ Address?: string; Name?: string }>;
}

export interface MailpitMessageList {
  messages: MailpitSummary[];
  total: number;
}

export async function listMailpitMessages(request: APIRequestContext): Promise<MailpitMessageList> {
  const config = readE2EConfig();
  const response = await request.get(new URL('/api/v1/messages?start=0&limit=1000', config.mailpitURL).toString());
  if (!response.ok()) throw new Error(`Mailpit message list returned HTTP ${response.status()}.`);
  return await response.json() as MailpitMessageList;
}

export async function getMailpitRaw(request: APIRequestContext, messageId: string): Promise<string> {
  const config = readE2EConfig();
  const response = await request.get(new URL(`/api/v1/message/${encodeURIComponent(messageId)}/raw`, config.mailpitURL).toString());
  if (!response.ok()) throw new Error(`Mailpit raw message endpoint returned HTTP ${response.status()}.`);
  return await response.text();
}
