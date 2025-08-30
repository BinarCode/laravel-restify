import * as citty from 'citty';

declare const main: citty.CommandDef<{
    command: {
        type: "positional";
        required: false;
    };
    cwd: {
        readonly type: "string";
        readonly description: "Specify the working directory";
        readonly valueHint: "directory";
        readonly default: ".";
    };
}>;

declare const runMain: () => Promise<void>;
declare function runCommand(name: string, argv?: string[], data?: {
    overrides?: Record<string, any>;
}): Promise<{
    result: unknown;
}>;

export { main, runCommand, runMain };
