declare module 'bcryptjs' {
  const bcrypt: {
    compareSync(plain: string, encrypted: string): boolean;
    hashSync(plain: string, rounds?: number): string;
  };
  export default bcrypt;
}
