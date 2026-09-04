const assert = require('node:assert/strict');
const path = require('node:path');

global.window = global;
require(path.join(__dirname, '../assets/js/editor/editor-chapters-save.js'));

async function wait(milliseconds) {
    return new Promise(resolve => setTimeout(resolve, milliseconds));
}

async function run() {
    const coordinator = global.almadenBookSaveCoordinator;
    let calls = 0;

    const first = coordinator.schedule(async () => {
        calls += 1;
        return 'old';
    }, 30);
    const second = coordinator.schedule(async () => {
        calls += 1;
        return 'latest';
    }, 10);

    assert.deepEqual(await Promise.all([first, second]), ['latest', 'latest']);
    assert.equal(calls, 1, 'Debounced saves must use only the latest state.');

    let releaseActive;
    let activeTasks = 0;
    let maxActiveTasks = 0;
    const blocker = new Promise(resolve => {
        releaseActive = resolve;
    });
    const active = coordinator.schedule(async () => {
        calls += 1;
        activeTasks += 1;
        maxActiveTasks = Math.max(maxActiveTasks, activeTasks);
        await blocker;
        activeTasks -= 1;
        return 'active';
    }, 0);
    await wait(10);
    const trailing = coordinator.schedule(async () => {
        calls += 1;
        activeTasks += 1;
        maxActiveTasks = Math.max(maxActiveTasks, activeTasks);
        activeTasks -= 1;
        return 'trailing';
    }, 0);
    await wait(10);

    assert.equal(calls, 2, 'A trailing save must wait for the active request.');
    assert.equal(coordinator.hasPending(), true);
    releaseActive();
    assert.deepEqual(await Promise.all([active, trailing]), ['active', 'trailing']);
    assert.equal(calls, 3);
    assert.equal(maxActiveTasks, 1, 'Save requests must never overlap.');
    assert.equal(coordinator.isSaving(), false);
    assert.equal(coordinator.hasPending(), false);

    console.log('Editor save coordinator behavior checks passed.');
}

run().catch(error => {
    console.error(error);
    process.exit(1);
});
