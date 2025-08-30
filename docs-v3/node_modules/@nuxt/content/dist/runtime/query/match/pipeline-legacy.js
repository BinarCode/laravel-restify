import { createPipelineFetcher } from "./pipeline.js";
export function createPipelineFetcherLegacy(getContentsList) {
  const _pipelineFetcher = createPipelineFetcher(getContentsList);
  return async (query) => {
    if (query.params().first) {
      query.withDirConfig();
    }
    const params = query.params();
    const result = await _pipelineFetcher(query);
    if (params.surround) {
      return result?.surround;
    }
    if (result?.dirConfig) {
      result.result = {
        _path: result.dirConfig?._path,
        ...result.result,
        _dir: result.dirConfig
      };
    }
    return result?.result;
  };
}
