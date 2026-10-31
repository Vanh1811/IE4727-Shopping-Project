import session, { type SessionData } from 'express-session';
import type { ResultSetHeader, RowDataPacket } from 'mysql2';
import type { Pool } from 'mysql2/promise';

const defaultTtlMs = 24 * 60 * 60 * 1000;
type SessionCallback = (error?: unknown) => void;
type SessionRow = RowDataPacket & { sess: string; expires: Date | string };

export class MysqlSessionStore extends session.Store {
  constructor(private readonly database: Pool) {
    super();
  }

  private expiresAt(value: SessionData): Date {
    const configured = value.cookie?.expires;
    const expires = configured ? new Date(configured) : new Date(Date.now() + defaultTtlMs);
    return Number.isNaN(expires.getTime()) ? new Date(Date.now() + defaultTtlMs) : expires;
  }

  override get(sid: string, callback: (error: unknown, value?: SessionData | null) => void): void {
    void this.database.execute<SessionRow[]>(
      'SELECT sess FROM sessions WHERE sid = ? AND expires > CURRENT_TIMESTAMP(3) LIMIT 1', [sid],
    ).then(([rows]) => {
      if (!rows[0]) return callback(null, null);
      try { callback(null, JSON.parse(rows[0].sess) as SessionData); }
      catch (error) { callback(error); }
    }).catch(callback);
  }

  override set(sid: string, value: SessionData, callback?: SessionCallback): void {
    let data: string;
    try { data = JSON.stringify(value); }
    catch (error) { callback?.(error); return; }
    void this.database.execute(
      `INSERT INTO sessions (sid, sess, expires) VALUES (?, ?, ?)
       ON DUPLICATE KEY UPDATE sess = VALUES(sess), expires = VALUES(expires)`,
      [sid, data, this.expiresAt(value)],
    ).then(() => callback?.()).catch((error: unknown) => callback?.(error));
  }

  override touch(sid: string, value: SessionData, callback?: () => void): void {
    void this.database.execute(
      'UPDATE sessions SET expires = ? WHERE sid = ?', [this.expiresAt(value), sid],
    ).then(() => callback?.()).catch(() => callback?.());
  }

  override destroy(sid: string, callback?: SessionCallback): void {
    void this.database.execute('DELETE FROM sessions WHERE sid = ?', [sid])
      .then(() => callback?.()).catch((error: unknown) => callback?.(error));
  }

  override all(callback: (error: unknown, sessions?: { [sid: string]: SessionData } | null) => void): void {
    void this.database.execute<SessionRow[]>(
      'SELECT sid, sess, expires FROM sessions WHERE expires > CURRENT_TIMESTAMP(3)',
    ).then(([rows]) => {
      const sessions: Record<string, SessionData> = {};
      for (const row of rows) {
        try { sessions[(row as SessionRow & { sid: string }).sid] = JSON.parse(row.sess) as SessionData; }
        catch (error) { return callback(error); }
      }
      callback(null, sessions);
    }).catch(callback);
  }

  override length(callback: (error: unknown, length?: number) => void): void {
    void this.database.execute<(RowDataPacket & { count: number })[]>(
      'SELECT COUNT(*) AS count FROM sessions WHERE expires > CURRENT_TIMESTAMP(3)',
    ).then(([rows]) => callback(null, Number(rows[0]?.count ?? 0))).catch(callback);
  }

  override clear(callback?: SessionCallback): void {
    void this.database.execute<ResultSetHeader>('DELETE FROM sessions')
      .then(() => callback?.()).catch((error: unknown) => callback?.(error));
  }

  async pruneExpired(): Promise<number> {
    const [result] = await this.database.execute<ResultSetHeader>(
      'DELETE FROM sessions WHERE expires <= CURRENT_TIMESTAMP(3)',
    );
    return result.affectedRows;
  }
}
