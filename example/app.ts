// import 'unfonts.css';
import ('./libs');
import {z} from './zepto';


await (() => new Promise((resolve) => {

    const wait = () => {
        if ('HSStaticMethods' in window) {

            return resolve(null)
        }

        requestAnimationFrame(wait)

    };

    wait()


}))()

console.debug('z', z);

console.log('Hello World!');
(document.querySelector('body') as HTMLElement).innerHTML = `<div class="flex flex-col gap-2 justify-center items-center w-full min-h-[100vh]">
<h1 class="text-2xl font-extrabold my-3">Hello World!</h1>
<p>If you are seeing this, this proves that the application works</p>
</div>`;


