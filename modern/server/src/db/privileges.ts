export function canInitializeDatabase(grants: string[], database: string, databaseExists: boolean): boolean {
  const normalized = grants.map((grant) => grant.toLowerCase());
  const globalAll = normalized.some((grant) => /all privileges on \*\.\*/.test(grant));
  const databaseAll = normalized.some((grant) => grant.includes(`all privileges on \`${database.toLowerCase()}\`.*`));
  if (globalAll || databaseAll) return true;
  if (databaseExists) {
    return normalized.some((grant) => /\b(create|alter)\b.*\bon\b/.test(grant) && grant.includes(`\`${database.toLowerCase()}\`.*`));
  }
  return normalized.some((grant) => /\bcreate\b.*\bon\s+\*\.\*/.test(grant));
}
