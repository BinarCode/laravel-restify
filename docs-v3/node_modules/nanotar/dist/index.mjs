const TAR_TYPE_FILE = 0;
const TAR_TYPE_DIR = 5;
function parseTar(data, opts) {
  const buffer = data.buffer || data;
  const files = [];
  let offset = 0;
  while (offset < buffer.byteLength - 512) {
    const name = _readString(buffer, offset, 100);
    if (name.length === 0) {
      break;
    }
    const mode = _readString(buffer, offset + 100, 8).trim();
    const uid = Number.parseInt(_readString(buffer, offset + 108, 8));
    const gid = Number.parseInt(_readString(buffer, offset + 116, 8));
    const size = _readNumber(buffer, offset + 124, 12);
    const seek = 512 + 512 * Math.trunc(size / 512) + (size % 512 ? 512 : 0);
    const mtime = _readNumber(buffer, offset + 136, 12);
    const _type = _readNumber(buffer, offset + 156, 1);
    const type = _type === TAR_TYPE_FILE ? "file" : _type === TAR_TYPE_DIR ? "directory" : _type;
    const user = _readString(buffer, offset + 265, 32);
    const group = _readString(buffer, offset + 297, 32);
    const meta = {
      name,
      type,
      size,
      attrs: {
        mode,
        uid,
        gid,
        mtime,
        user,
        group
      }
    };
    if (opts?.filter && !opts.filter(meta)) {
      offset += seek;
      continue;
    }
    if (opts?.metaOnly) {
      files.push(meta);
      offset += seek;
      continue;
    }
    const data2 = _type === TAR_TYPE_DIR ? undefined : new Uint8Array(buffer, offset + 512, size);
    files.push({
      ...meta,
      data: data2,
      get text() {
        return new TextDecoder().decode(this.data);
      }
    });
    offset += seek;
  }
  return files;
}
async function parseTarGzip(data, opts = {}) {
  const stream = new ReadableStream({
    start(controller) {
      controller.enqueue(new Uint8Array(data));
      controller.close();
    }
  }).pipeThrough(new DecompressionStream(opts.compression ?? "gzip"));
  const decompressedData = await new Response(stream).arrayBuffer();
  return parseTar(decompressedData, opts);
}
function _readString(buffer, offset, size) {
  const view = new Uint8Array(buffer, offset, size);
  const i = view.indexOf(0);
  const td = new TextDecoder();
  return td.decode(i === -1 ? view : view.slice(0, i));
}
function _readNumber(buffer, offset, size) {
  const view = new Uint8Array(buffer, offset, size);
  let str = "";
  for (let i = 0; i < size; i++) {
    str += String.fromCodePoint(view[i]);
  }
  return Number.parseInt(str, 8);
}

function createTar(files, opts = {}) {
  const _files = files.map((file) => {
    const data = _normalizeData(file.data);
    return {
      ...file,
      data,
      size: data?.length || 0
    };
  });
  let tarDataSize = 0;
  for (let i = 0; i < files.length; i++) {
    const size = _files[i].data?.length ?? 0;
    tarDataSize += 512 + 512 * Math.trunc(size / 512);
    if (size % 512) {
      tarDataSize += 512;
    }
  }
  let bufSize = 10240 * Math.trunc(tarDataSize / 10240);
  if (tarDataSize % 10240) {
    bufSize += 10240;
  }
  const buffer = new ArrayBuffer(bufSize);
  let offset = 0;
  for (const file of _files) {
    const isDir = !file.data;
    _writeString(buffer, file.name, offset, 100);
    const mode = file.attrs?.mode ?? opts.attrs?.mode ?? (isDir ? "775" : "664");
    _writeString(buffer, _leftPad(mode, 7), offset + 100, 8);
    const uid = file.attrs?.uid ?? opts.attrs?.uid ?? 1e3;
    _writeString(buffer, _leftPad(uid.toString(8), 7), offset + 108, 8);
    const gid = file.attrs?.gid ?? opts.attrs?.gid ?? 1e3;
    _writeString(buffer, _leftPad(gid.toString(8), 7), offset + 116, 8);
    _writeString(buffer, _leftPad(file.size.toString(8), 11), offset + 124, 12);
    const mtime = file.attrs?.mtime ?? opts.attrs?.mtime ?? Date.now();
    _writeString(
      buffer,
      _leftPad(Math.trunc(mtime / 1e3).toString(8), 11),
      offset + 136,
      12
    );
    const type = isDir ? "5" : "0";
    _writeString(buffer, type, offset + 156, 1);
    _writeString(
      buffer,
      "ustar",
      offset + 257,
      6
      /* magic string */
    );
    _writeString(
      buffer,
      "00",
      offset + 263,
      2
      /* magic version */
    );
    const user = file.attrs?.user ?? opts.attrs?.user ?? "";
    _writeString(buffer, user, offset + 265, 32);
    const group = file.attrs?.group ?? opts.attrs?.group ?? "";
    _writeString(buffer, group, offset + 297, 32);
    _writeString(buffer, "        ", offset + 148, 8);
    const header = new Uint8Array(buffer, offset, 512);
    let chksum = 0;
    for (let i = 0; i < 512; i++) {
      chksum += header[i];
    }
    _writeString(buffer, chksum.toString(8), offset + 148, 8);
    if (!isDir) {
      const destArray = new Uint8Array(buffer, offset + 512, file.size);
      for (let byteIdx = 0; byteIdx < file.size; byteIdx++) {
        destArray[byteIdx] = file.data[byteIdx];
      }
      offset += 512 * Math.trunc(file.size / 512);
      if (file.size % 512) {
        offset += 512;
      }
    }
    offset += 512;
  }
  return new Uint8Array(buffer);
}
function createTarGzipStream(files, opts = {}) {
  const buffer = createTar(files, opts);
  return new ReadableStream({
    start(controller) {
      controller.enqueue(buffer);
      controller.close();
    }
  }).pipeThrough(new CompressionStream(opts.compression ?? "gzip"));
}
async function createTarGzip(files, opts = {}) {
  const data = await new Response(createTarGzipStream(files, opts)).arrayBuffer().then((buffer) => new Uint8Array(buffer));
  return data;
}
function _writeString(buffer, str, offset, size) {
  const strView = new Uint8Array(buffer, offset, size);
  const te = new TextEncoder();
  const written = te.encodeInto(str, strView).written;
  for (let i = written; i < size; i++) {
    strView[i] = 0;
  }
}
function _leftPad(input, targetLength) {
  return String(input).padStart(targetLength, "0");
}
function _normalizeData(data) {
  if (data === null || data === undefined) {
    return undefined;
  }
  if (typeof data === "string") {
    return new TextEncoder().encode(data);
  }
  if (data instanceof ArrayBuffer) {
    return new Uint8Array(data);
  }
  return data;
}

export { createTar, createTarGzip, createTarGzipStream, parseTar, parseTarGzip };
