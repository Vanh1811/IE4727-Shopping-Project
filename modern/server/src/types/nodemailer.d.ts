declare module 'nodemailer' {
  type TransportOptions = {
    host: string;
    port: number;
    secure: boolean;
    ignoreTLS: boolean;
    connectionTimeout: number;
    greetingTimeout: number;
    socketTimeout: number;
  };
  type MessageOptions = {
    from: string;
    to: string;
    subject: string;
    text: string;
    disableFileAccess: boolean;
    disableUrlAccess: boolean;
  };
  type SentMessageInfo = { accepted: Array<string | { address: string }> };
  type Transporter = { sendMail(options: MessageOptions): Promise<SentMessageInfo>; close(): void };
  const nodemailer: { createTransport(options: TransportOptions): Transporter };
  export default nodemailer;
}
