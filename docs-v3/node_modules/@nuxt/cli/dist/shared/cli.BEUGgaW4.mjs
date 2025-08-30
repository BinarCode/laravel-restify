import process from 'node:process';
import { l as logger } from './cli.B9AmABr3.mjs';

function overrideEnv(targetEnv) {
  const currentEnv = process.env.NODE_ENV;
  if (currentEnv && currentEnv !== targetEnv) {
    logger.warn(
      `Changing \`NODE_ENV\` from \`${currentEnv}\` to \`${targetEnv}\`, to avoid unintended behavior.`
    );
  }
  process.env.NODE_ENV = targetEnv;
}

export { overrideEnv as o };
