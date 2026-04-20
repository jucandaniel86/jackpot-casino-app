#!/usr/bin/env node

const {
  Connection,
  Keypair,
  PublicKey,
  Transaction
} = require('@solana/web3.js');

const {
  createTransferInstruction
} = require('@solana/spl-token');
const {
  TOKEN_PROGRAM_ID,
  TOKEN_2022_PROGRAM_ID
} = require('@solana/spl-token');

const fs = require('fs');

// ---------- args ----------
const args = process.argv.slice(2);
const getArg = (k) => {
  const i = args.indexOf(k);
  return i !== -1 ? args[i + 1] : null;
};

const rpcUrl = getArg('--rpc');
const mint = getArg('--mint');
const fromTokenAccount = getArg('--fromTokenAccount');
const toTokenAccount = getArg('--toTokenAccount');
const amountBase = getArg('--amountBase');

if (!rpcUrl || !mint || !fromTokenAccount || !toTokenAccount || !amountBase) {
  console.error('Missing arguments');
  process.exit(1);
}

// ---------- read secrets from stdin ----------
const stdin = fs.readFileSync(0, 'utf8').trim();
let payload = null;
try {
  payload = JSON.parse(stdin);
} catch (e) {
  console.error('Invalid stdin payload (expected JSON)');
  process.exit(2);
}

const userSecretB64 = payload && payload.userSecretB64;
const feePayerSecretB64 = payload && payload.feePayerSecretB64;

if (!userSecretB64 || !feePayerSecretB64) {
  console.error('Missing secrets in payload');
  process.exit(2);
}

const userSecret = Uint8Array.from(Buffer.from(userSecretB64, 'base64'));
const feePayerSecret = Uint8Array.from(Buffer.from(feePayerSecretB64, 'base64'));

const user = Keypair.fromSecretKey(userSecret);
const feePayer = Keypair.fromSecretKey(feePayerSecret);

// ---------- solana ----------
const connection = new Connection(rpcUrl, 'confirmed');

const main = async () => {
  const mintPubkey = new PublicKey(mint);
  const fromPubkey = new PublicKey(fromTokenAccount);
  const toPubkey = new PublicKey(toTokenAccount);

  const mintInfo = await connection.getAccountInfo(mintPubkey);
  if (!mintInfo) {
    console.error('Mint account not found', JSON.stringify({ mint }));
    process.exit(2);
  }

  const programId = mintInfo.owner.equals(TOKEN_2022_PROGRAM_ID)
    ? TOKEN_2022_PROGRAM_ID
    : TOKEN_PROGRAM_ID;

  const fromInfo = await connection.getParsedAccountInfo(fromPubkey);
  const toInfo = await connection.getParsedAccountInfo(toPubkey);

  const fromParsed = fromInfo && fromInfo.value && fromInfo.value.data && fromInfo.value.data.parsed;
  const toParsed = toInfo && toInfo.value && toInfo.value.data && toInfo.value.data.parsed;

  if (!fromParsed || !toParsed) {
    console.error('Token account not initialized', JSON.stringify({
      fromTokenAccount,
      toTokenAccount
    }));
    process.exit(2);
  }

  const fromMint = fromParsed.info && fromParsed.info.mint;
  const toMint = toParsed.info && toParsed.info.mint;
  const fromOwner = fromParsed.info && fromParsed.info.owner;
  const fromAmount = fromParsed.info && fromParsed.info.tokenAmount && fromParsed.info.tokenAmount.amount;
  const toAmount = toParsed.info && toParsed.info.tokenAmount && toParsed.info.tokenAmount.amount;

  console.error('Token account debug', JSON.stringify({
    programId: programId.toBase58(),
    fromTokenAccount,
    toTokenAccount,
    fromOwner,
    fromMint,
    toMint,
    fromAmount,
    toAmount
  }));

  if (fromOwner !== user.publicKey.toBase58()) {
    console.error('Token owner mismatch', JSON.stringify({
      fromOwner,
      signer: user.publicKey.toBase58(),
      fromTokenAccount
    }));
    process.exit(2);
  }

  if (fromMint !== toMint) {
    console.error('Mint mismatch', JSON.stringify({
      fromMint,
      toMint,
      fromTokenAccount,
      toTokenAccount
    }));
    process.exit(2);
  }

  const tx = new Transaction().add(
    createTransferInstruction(
      fromPubkey,
      toPubkey,
      user.publicKey,
      BigInt(amountBase),
      [],
      programId
    )
  );

  try {
    const { blockhash } = await connection.getLatestBlockhash('confirmed');
    tx.recentBlockhash = blockhash;
    tx.feePayer = feePayer.publicKey;
    tx.sign(user, feePayer);

    const sig = await connection.sendRawTransaction(tx.serialize());
    await connection.confirmTransaction(sig, 'confirmed');
    console.log(sig);
  } catch (e) {
    console.error((e && e.message) ? e.message : e);
    process.exit(2);
  }
};

main();
