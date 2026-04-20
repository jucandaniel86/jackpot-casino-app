export const useUtils = () => {
  const removeDuplicates = (arr: any[], searchedKey: string) => {
    return arr.reduce((unique, o) => {
      if (!unique.some((obj: any) => obj[searchedKey] === o[searchedKey])) {
        unique.push(o);
      }
      return unique;
    }, []);
  };

  function findRecursive(search: string, key: string, arr: any[]) {
    return arr.filter(function f(o) {
      return (
        o[key].includes(search) ||
        (o.children && (o.children = o.children.filter(f)).length)
      );
    });
  }

  function flatten(destArray: any[], nodeList: any[]) {
    nodeList.forEach((node) => {
      destArray.push(node);
      flatten(destArray, node.descendants || []);
    });
  }

  const wait = (duration: number): Promise<void> =>
    new Promise((resolve) => {
      setTimeout(() => resolve(), duration);
    });

  const verifyNullObject = (obj: any, _keys: string[]) => {
    return (
      obj &&
      obj !== "null" &&
      obj !== "undefined" &&
      Object.keys(obj).every((ob) => _keys.indexOf(ob) !== -1)
    );
  };

  return {
    removeDuplicates,
    findRecursive,
    flatten,
    wait,
    verifyNullObject,
  };
};
