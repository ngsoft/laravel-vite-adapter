// import './zepto';
import('./zepto');

await (() => new Promise((resolve) => {

    const wait = () => {
        if ('$' in window) {
            return resolve(null)
        }

        requestAnimationFrame(wait)

    };

    wait()


}))()

// console.debug('zz', zz);


// svelte compatibility

export const z = window.$ as ZeptoStatic;
